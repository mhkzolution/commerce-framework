/**
 * Barcode Center — source-agnostic queue item validation.
 */

/** @typedef {import('./queue-item.js').QueueItem} QueueItem */

const MAX_BARCODE_LENGTH = 100;
const CODE128_PATTERN = /^[\x20-\x7E]+$/;

/**
 * @param {QueueItem | Omit<QueueItem, 'id'>} item
 * @param {Record<string, string>} [i18n]
 * @returns {string|null}
 */
export function validateQueueItem(item, i18n = {}) {
    const barcode = String(item.barcode ?? '').trim();

    if (!barcode) {
        return i18n.barcodeRequired || 'Barcode is required.';
    }

    if (barcode.length > MAX_BARCODE_LENGTH) {
        return i18n.barcodeTooLong || `Barcode must be at most ${MAX_BARCODE_LENGTH} characters.`;
    }

    if (!CODE128_PATTERN.test(barcode)) {
        return i18n.barcodeInvalidFormat || 'Barcode contains invalid characters for CODE128.';
    }

    if (!String(item.title ?? '').trim()) {
        return i18n.titleRequired || 'Title is required.';
    }

    if (!String(item.owner_name ?? '').trim()) {
        return i18n.ownerRequired || 'Owner name is required.';
    }

    const quantity = Number(item.quantity) || 0;
    if (quantity < 1 || quantity > 10000) {
        return i18n.quantityInvalid || 'Quantity must be between 1 and 10,000.';
    }

    return null;
}

/**
 * @param {Omit<QueueItem, 'id'>} item
 * @param {Array<QueueItem>} queue
 * @param {Record<string, string>} [i18n]
 * @returns {string|null}
 */
export function validateManualQueueItem(item, queue, i18n = {}) {
    const baseError = validateQueueItem(item, i18n);
    if (baseError) {
        return baseError;
    }

    return null;
}
