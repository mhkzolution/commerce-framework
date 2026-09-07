import assert from 'node:assert/strict';
import { test } from 'node:test';
import { generateSku, ProductWorkspaceState, variantIdentityKey } from './state.js';
import { applyVariantBuilderNotification } from './variant-builder-render.js';

test('variable SKU prefix beats the pattern tokens', () => {
    assert.equal(
        generateSku('{PRODUCT}-{COLOR}-{SIZE}', 'tee', { color: 'Red', size: 'S' }, 'TSHIRT'),
        'TSHIRT-RED-S',
    );
});

test('serialize copies product trackInventory onto every variant', () => {
    const state = new ProductWorkspaceState({
        product: { type: 'variable', trackInventory: false, skuPrefix: 'TSHIRT' },
        variants: [
            { id: 'a', sku: 'TSHIRT-RED', stock: { onHand: 2 }, options: { color: 'Red' } },
            { id: 'b', sku: 'TSHIRT-BLUE', stock: { onHand: 0 }, options: { color: 'Blue' } },
        ],
    });

    const payload = JSON.parse(state.serialize());

    assert.equal(payload.product.sku, 'TSHIRT');
    assert.equal(payload.variants[0].trackInventory, false);
    assert.equal(payload.variants[1].trackInventory, false);
    assert.equal(payload.variants[0].onHand, 2);
});

test('type switch keeps the variable prefix separate from the simple SKU', () => {
    const state = new ProductWorkspaceState({
        product: { type: 'variable', skuPrefix: 'TSHIRT', sku: '' },
        variants: [
            { id: 'a', sku: 'TSHIRT-RED', price: '10', stock: { onHand: 3 } },
        ],
    });

    state.setType('simple');
    assert.equal(state.getState().product.sku, 'TSHIRT-RED');
    assert.equal(state.getState().product.skuPrefix, 'TSHIRT');
    assert.equal(state.skuInputValue(), 'TSHIRT-RED');

    state.setType('variable');
    assert.equal(state.getState().product.skuPrefix, 'TSHIRT');
    assert.equal(state.skuInputValue(), 'TSHIRT');
    assert.equal(JSON.parse(state.serialize()).product.sku, 'TSHIRT');
});

test('on-hand edits do not rebuild the variant grid', () => {
    const state = new ProductWorkspaceState();
    const variantId = state.getState().variants[0].id;
    const lastEpoch = state.getState().uiEpoch;
    let gridRenders = 0;

    state.updateVariantStock(variantId, 'onHand', '12');
    const result = applyVariantBuilderNotification(state.getState().uiEpoch, lastEpoch, {
        renderGrid: () => {
            gridRenders += 1;
        },
        renderOptions: () => {},
    });

    assert.equal(result.rebuilt, false);
    assert.equal(gridRenders, 0);
    assert.equal(state.getState().variants[0].stock.onHand, '12');
});

test('structural edits rebuild the variant grid', () => {
    let gridRenders = 0;
    const result = applyVariantBuilderNotification(2, 1, {
        renderGrid: () => {
            gridRenders += 1;
        },
        renderOptions: () => {},
    });

    assert.equal(result.rebuilt, true);
    assert.equal(result.lastEpoch, 2);
    assert.equal(gridRenders, 1);
});

test('generateMatrix uses the product SKU prefix', () => {
    const state = new ProductWorkspaceState({
        product: { type: 'variable', skuPrefix: 'TSHIRT', slug: 'tee' },
        options: [{ id: 'opt', name: 'Color', values: ['Red'] }],
        variants: [{ id: 'seed', sku: '', stock: { onHand: 0 } }],
    });

    state.generateMatrix();

    assert.equal(state.getState().variants[0].sku, 'TSHIRT-RED');
    assert.ok(state.getState().uiEpoch > 0);
});

test('variantIdentityKey matches PHP VariantIdentity::key', () => {
    assert.equal(variantIdentityKey([5, 1]), '1-5');
    assert.equal(variantIdentityKey(['5', '1']), '1-5');
    assert.equal(variantIdentityKey([10, 2, 3]), '2-3-10');
});

test('serialize always includes productAttributes and generateVariants false by default', () => {
    const state = new ProductWorkspaceState({
        product: { type: 'variable', skuPrefix: 'TSHIRT' },
        productAttributes: [
            { attributeId: 7, usedForVariations: true, valueIds: [3, 1], newLabels: ['Burgundy'], position: 0 },
        ],
    });

    const payload = JSON.parse(state.serialize());

    assert.ok(Array.isArray(payload.productAttributes));
    assert.equal(payload.productAttributes.length, 1);
    assert.equal(payload.productAttributes[0].attributeId, 7);
    assert.equal(payload.productAttributes[0].usedForVariations, true);
    assert.deepEqual(payload.productAttributes[0].valueIds, [3, 1]);
    assert.deepEqual(payload.productAttributes[0].newLabels, ['Burgundy']);
    assert.equal(payload.generateVariants, false);
    assert.equal(payload.variants[0].valueIds, undefined);
});

function apparelAttributeSets() {
    return [{
        id: 10,
        attributes: [
            {
                id: 1,
                name: 'Color',
                type: 'select',
                values: [
                    { id: 5, label: 'Red', code: 'red' },
                    { id: 1, label: 'Blue', code: 'blue' },
                ],
            },
            {
                id: 2,
                name: 'Size',
                type: 'select',
                values: [
                    { id: 9, label: 'S', code: 's' },
                ],
            },
        ],
    }];
}

test('generate from attributes sets generateVariants once and rematches identity', () => {
    const state = new ProductWorkspaceState({
        product: { type: 'variable', skuPrefix: 'TSHIRT', slug: 'tee' },
        attributeSets: apparelAttributeSets(),
        productAttributes: [
            { attributeId: 1, name: 'Color', usedForVariations: true, valueIds: [5, 1], position: 0 },
        ],
        variants: [{
            id: 'kept',
            uuid: 'uuid-red',
            sku: 'TSHIRT-RED',
            stock: { onHand: 4, reserved: 0, available: 4 },
            valueIds: [5],
        }],
    });

    state.generateFromAttributes();
    const kept = state.getState().variants.find((row) => variantIdentityKey(row.valueIds ?? []) === '5');
    assert.equal(kept?.uuid, 'uuid-red');
    assert.equal(kept?.sku, 'TSHIRT-RED');
    assert.equal(state.getState().variants.length, 2);

    const first = JSON.parse(state.serialize());
    assert.equal(first.generateVariants, true);
    assert.equal(first.variants.length, 1);
    assert.equal(first.variants[0].uuid, 'uuid-red');
    assert.ok(first.variants.every((row) => row.valueIds === undefined));
    assert.ok(first.variants.every((row) => String(row.uuid ?? '').trim() !== ''));

    state.consumeGenerateFlag();
    const second = JSON.parse(state.serialize());
    assert.equal(second.generateVariants, false);
    assert.ok(Array.isArray(second.productAttributes));
    assert.equal(second.productAttributes[0].attributeId, 1);
    assert.ok(second.variants.every((row) => String(row.uuid ?? '').trim() !== ''));
});

test('generate serialize omits identity-less preview rows', () => {
    const state = new ProductWorkspaceState({
        product: { type: 'variable', skuPrefix: 'TSHIRT', slug: 'tee' },
        attributeSets: apparelAttributeSets(),
        productAttributes: [
            { attributeId: 1, name: 'Color', usedForVariations: true, valueIds: [5, 1], position: 0 },
        ],
        variants: [{
            id: 'kept',
            uuid: 'uuid-red',
            sku: 'TSHIRT-RED',
            stock: { onHand: 4 },
            valueIds: [5],
        }],
    });

    state.generateFromAttributes();
    const payload = JSON.parse(state.serialize());

    assert.equal(payload.generateVariants, true);
    assert.ok(payload.variants.every((row) => row.uuid != null && String(row.uuid).trim() !== ''));
    assert.equal(payload.variants.some((row) => row.uuid == null || row.uuid === ''), false);
    assert.equal(payload.variants.length, 1);
});

test('generate preview uses labels and codes while identity key matches PHP', () => {
    const state = new ProductWorkspaceState({
        product: { type: 'variable', skuPrefix: 'TSHIRT', slug: 'tee' },
        attributeSets: apparelAttributeSets(),
        productAttributes: [
            { attributeId: 1, name: 'Color', usedForVariations: true, valueIds: [5, 1], position: 0 },
            { attributeId: 2, name: 'Size', usedForVariations: true, valueIds: [9], position: 1 },
        ],
        variants: [{
            id: 'kept',
            uuid: 'uuid-red-s',
            sku: 'TSHIRT-RED-S',
            name: 'Red / S',
            stock: { onHand: 2 },
            valueIds: [5, 9],
        }],
    });

    state.generateFromAttributes();

    assert.equal(variantIdentityKey([5, 1, 9]), '1-5-9');
    const redS = state.getState().variants.find((row) => variantIdentityKey(row.valueIds ?? []) === '5-9');
    const blueS = state.getState().variants.find((row) => variantIdentityKey(row.valueIds ?? []) === '1-9');
    assert.equal(redS?.uuid, 'uuid-red-s');
    assert.equal(redS?.name, 'Red / S');
    assert.equal(blueS?.name, 'Blue / S');
    assert.equal(blueS?.sku, 'TSHIRT-BLUE-S');
    assert.equal(blueS?.name.includes('1'), false);
    assert.equal(String(blueS?.sku).includes('-1-'), false);
    assert.equal(variantIdentityKey(blueS?.valueIds ?? []), '1-9');

    const payload = JSON.parse(state.serialize());
    assert.equal(payload.generateVariants, true);
    assert.deepEqual(payload.variants.map((row) => row.uuid), ['uuid-red-s']);
});

test('generate is blocked until each variation axis has a value', () => {
    const state = new ProductWorkspaceState({
        product: { type: 'variable' },
        productAttributes: [
            { attributeId: 1, usedForVariations: true, valueIds: [1] },
            { attributeId: 2, usedForVariations: true, valueIds: [] },
        ],
    });

    assert.equal(state.canGenerateVariants(), false);
    state.setAttributeValues(2, [9]);
    assert.equal(state.canGenerateVariants(), true);
});

test('unchecking used for variations confirms when a matrix exists', () => {
    const state = new ProductWorkspaceState({
        product: { type: 'variable' },
        productAttributes: [
            { attributeId: 1, usedForVariations: true, valueIds: [5] },
        ],
        variants: [{ id: 'a', uuid: 'u1', valueIds: [5], stock: { onHand: 0 } }],
        labels: { usedForVariationsUncheckConfirm: 'warn' },
    });

    let asked = '';
    const blocked = state.setUsedForVariations(1, false, (message) => {
        asked = message;
        return false;
    });

    assert.equal(blocked, false);
    assert.equal(asked, 'warn');
    assert.equal(state.getState().productAttributes[0].usedForVariations, true);

    assert.equal(state.setUsedForVariations(1, false, () => true), true);
    assert.equal(state.getState().productAttributes[0].usedForVariations, false);
});

test('simple serialize forces usedForVariations false', () => {
    const state = new ProductWorkspaceState({
        product: { type: 'simple' },
        productAttributes: [
            { attributeId: 4, usedForVariations: true, valueIds: [2] },
        ],
    });

    const payload = JSON.parse(state.serialize());
    assert.equal(payload.productAttributes[0].usedForVariations, false);
    assert.equal(payload.generateVariants, false);
});
