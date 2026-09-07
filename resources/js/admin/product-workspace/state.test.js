import assert from 'node:assert/strict';
import { test } from 'node:test';
import { generateSku, ProductWorkspaceState } from './state.js';

test('variable SKU prefix beats the pattern tokens', () => {
    assert.equal(
        generateSku('{PRODUCT}-{COLOR}-{SIZE}', 'tee', { color: 'Red', size: 'S' }, 'TSHIRT'),
        'TSHIRT-RED-S',
    );
});

test('serialize copies product trackInventory onto every variant', () => {
    const state = new ProductWorkspaceState({
        product: { type: 'variable', trackInventory: false, sku: 'TSHIRT' },
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

test('on-hand edits do not bump uiEpoch so the grid can keep focus', () => {
    const state = new ProductWorkspaceState();
    const variantId = state.getState().variants[0].id;
    const epoch = state.getState().uiEpoch;

    state.updateVariantStock(variantId, 'onHand', '12');

    assert.equal(state.getState().uiEpoch, epoch);
    assert.equal(state.getState().variants[0].stock.onHand, '12');
    assert.equal(state.getState().dirty, true);
});

test('generateMatrix uses the product SKU prefix', () => {
    const state = new ProductWorkspaceState({
        product: { type: 'variable', sku: 'TSHIRT', slug: 'tee' },
        options: [{ id: 'opt', name: 'Color', values: ['Red'] }],
        variants: [],
    });

    state.generateMatrix();

    assert.equal(state.getState().variants[0].sku, 'TSHIRT-RED');
    assert.ok(state.getState().uiEpoch > 0);
});
