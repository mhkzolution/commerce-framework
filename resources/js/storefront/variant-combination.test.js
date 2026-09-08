import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
    combinationExists,
    isAxisValueEnabled,
    resolveExactVariant,
} from './variant-combination.js';

const variants = [
    {
        uuid: 'red-s',
        sku: 'RED-S',
        in_stock: true,
        options: { color: 'Red', size: 'S' },
    },
    {
        uuid: 'blue-m',
        sku: 'BLUE-M',
        in_stock: false,
        options: { color: 'Blue', size: 'M' },
    },
];

test('Blue-S is missing so Size S is disabled when Color is Blue', () => {
    const selections = { color: 'Blue', size: 'M' };

    assert.equal(isAxisValueEnabled(variants, selections, 'size', 'S'), false);
    assert.equal(isAxisValueEnabled(variants, selections, 'size', 'M'), true);
    assert.equal(isAxisValueEnabled(variants, selections, 'color', 'Blue'), true);
    assert.equal(isAxisValueEnabled(variants, selections, 'color', 'Red'), false);
});

test('existing Blue-M combo stays selectable when it is out of stock', () => {
    const selections = { color: 'Blue', size: 'M' };

    assert.equal(combinationExists(variants, selections), true);
    assert.equal(isAxisValueEnabled(variants, selections, 'size', 'M'), true);
    assert.equal(resolveExactVariant(variants, selections)?.uuid, 'blue-m');
    assert.equal(resolveExactVariant(variants, selections)?.in_stock, false);
});

test('does not auto-switch to a nearby variant when a combo is missing', () => {
    assert.equal(resolveExactVariant(variants, { color: 'Blue', size: 'S' }), null);
    assert.equal(resolveExactVariant(variants, { color: 'Red', size: 'M' }), null);
});
