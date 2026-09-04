import { getPermissions } from './api.js';

const MODE_ACTIONS = {
    'stock-check': [
        { action: 'found', labelKey: 'found', hotkey: '1', variant: 'primary' },
        { action: 'not_found', labelKey: 'not_found', hotkey: '2' },
        { action: 'damaged', labelKey: 'damaged', hotkey: '3' },
        { action: 'wrong_location', labelKey: 'wrong_location', hotkey: '4' },
        { action: 'move', labelKey: 'move', hotkey: '5' },
        { action: 'adjust_stock', labelKey: 'adjust_stock', hotkey: '6', permission: 'adjust' },
        { action: 'view_product', labelKey: 'view_product', hotkey: '7' },
    ],
    'label-attachment': [
        { action: 'attached', labelKey: 'attached', hotkey: '1', variant: 'primary' },
        { action: 'skip', labelKey: 'skip', hotkey: '2' },
        { action: 'view_product', labelKey: 'view_product', hotkey: '3' },
    ],
    receiving: [
        { action: 'receive', labelKey: 'receive', hotkey: '1', variant: 'primary', needsQty: true },
        { action: 'skip', labelKey: 'skip', hotkey: '2' },
        { action: 'view_product', labelKey: 'view_product', hotkey: '3' },
    ],
    picking: [
        { action: 'correct', labelKey: 'correct', hotkey: '1', variant: 'primary' },
        { action: 'wrong_item', labelKey: 'wrong_item', hotkey: '2' },
        { action: 'skip', labelKey: 'skip', hotkey: '3' },
    ],
    packing: [
        { action: 'pack', labelKey: 'pack', hotkey: '1', variant: 'primary' },
        { action: 'complete', labelKey: 'complete', hotkey: '2', variant: 'primary' },
        { action: 'skip', labelKey: 'skip', hotkey: '3' },
    ],
    transfer: [
        { action: 'transfer', labelKey: 'transfer', hotkey: '1', variant: 'primary', needsQty: true, permission: 'transfer' },
        { action: 'skip', labelKey: 'skip', hotkey: '2' },
    ],
    'inventory-count': [
        { action: 'save', labelKey: 'save', hotkey: '1', variant: 'primary', needsQty: true },
        { action: 'skip', labelKey: 'skip', hotkey: '2' },
    ],
};

const MODE_LABELS = {
    'stock-check': 'Stock Check',
    'label-attachment': 'Label Attachment',
    receiving: 'Receiving',
    picking: 'Picking',
    packing: 'Packing',
    transfer: 'Transfer',
    'inventory-count': 'Inventory Count',
};

export function getModeActions(mode) {
    const permissions = getPermissions();
    return (MODE_ACTIONS[mode] || []).filter((item) => {
        if (!item.permission) return true;
        return Boolean(permissions[item.permission]);
    });
}

export function getModeLabel(mode) {
    return MODE_LABELS[mode] || mode;
}

export function modeNeedsQty(mode) {
    return ['receiving', 'transfer', 'inventory-count'].includes(mode);
}

export function getPrimaryAction(mode) {
    return getModeActions(mode).find((action) => action.variant === 'primary') || null;
}

export { MODE_ACTIONS };
