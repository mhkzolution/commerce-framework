import assert from 'node:assert/strict';
import { test } from 'node:test';
import { generateSku, ProductWorkspaceState } from './state.js';
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
