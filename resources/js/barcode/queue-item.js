/**
 * Barcode Center — canonical queue item types and source adapters.
 */

/** @typedef {'PRODUCT' | 'MANUAL'} QueueSource */

/**
 * @typedef {Object} QueueItem
 * @property {string} id
 * @property {QueueSource} source
 * @property {string} title
 * @property {string} barcode
 * @property {string} display_text
 * @property {string} owner_name
 * @property {number} quantity
 * @property {string|null} [thumbnail_url]
 * @property {string|null} [variant_id]
 * @property {string|null} [product_id]
 * @property {Record<string, unknown>} [meta]
 */

/**
 * @typedef {Object} ExpandedLabel
 * @property {string} owner_name
 * @property {string} barcode
 * @property {string} display_text
 */

/**
 * @typedef {Object} ProductSearchResult
 * @property {string} variant_uuid
 * @property {string|null} thumbnail_url
 * @property {string} owner_name
 * @property {string} product_name
 * @property {string} variant_name
 * @property {string} sku
 */

/**
 * @param {QueueItem} item
 * @returns {string}
 */
export function queueItemKey(item) {
    return `${item.source}:${item.barcode}`;
}

/**
 * @param {ProductSearchResult} result
 * @param {number} [quantity]
 * @returns {Omit<QueueItem, 'id'>}
 */
export function productQueueItemFromSearchResult(result, quantity = 1) {
    const barcode = String(result.sku ?? '').trim();

    return {
        source: 'PRODUCT',
        title: String(result.product_name ?? ''),
        barcode,
        display_text: barcode,
        owner_name: String(result.owner_name ?? ''),
        quantity: Math.max(1, quantity),
        thumbnail_url: result.thumbnail_url ?? null,
        variant_id: String(result.variant_uuid ?? ''),
        product_id: null,
        meta: {
            variant_name: String(result.variant_name ?? ''),
        },
    };
}

/**
 * @param {string} siteName
 * @param {Array<{uuid: string, name: string}>} sellers
 * @param {string|null|undefined} sellerUuid
 * @returns {string}
 */
export function resolveOwnerName(siteName, sellers, sellerUuid) {
    if (!sellerUuid) {
        return siteName;
    }

    const seller = sellers.find((entry) => entry.uuid === sellerUuid);

    return seller?.name || siteName;
}

/**
 * @param {{ name: string, barcode: string, sku?: string, seller_uuid?: string|null, owner_name?: string }} input
 * @param {number} [quantity]
 * @param {{ siteName: string, sellers: Array<{uuid: string, name: string}> }} context
 * @returns {Omit<QueueItem, 'id'>}
 */
export function manualQueueItemFromInput(input, quantity = 1, context = { siteName: '', sellers: [] }) {
    const barcode = String(input.barcode ?? '').trim();
    const displayText = String(input.sku ?? '').trim();
    const sellerUuid = input.seller_uuid ? String(input.seller_uuid) : null;
    const ownerName = input.owner_name?.trim()
        || resolveOwnerName(context.siteName, context.sellers, sellerUuid);

    return {
        source: 'MANUAL',
        title: String(input.name ?? '').trim(),
        barcode,
        display_text: displayText !== '' ? displayText : barcode,
        owner_name: ownerName,
        quantity: Math.max(1, quantity),
        thumbnail_url: null,
        variant_id: null,
        product_id: null,
        meta: sellerUuid ? { seller_uuid: sellerUuid } : {},
    };
}

/**
 * @param {QueueItem} item
 * @returns {ExpandedLabel}
 */
export function queueItemToExpandedLabel(item) {
    return {
        owner_name: item.owner_name,
        barcode: item.barcode,
        display_text: item.display_text,
    };
}

/**
 * @param {Record<string, unknown>} raw
 * @returns {Omit<QueueItem, 'id'>}
 */
export function queueItemFromPayload(raw) {
    if (raw.source && raw.barcode && raw.title) {
        return {
            source: /** @type {QueueSource} */ (String(raw.source)),
            title: String(raw.title),
            barcode: String(raw.barcode),
            display_text: String(raw.display_text ?? raw.barcode),
            owner_name: String(raw.owner_name ?? ''),
            quantity: Math.max(1, Number(raw.quantity) || 1),
            thumbnail_url: raw.thumbnail_url ? String(raw.thumbnail_url) : null,
            variant_id: raw.variant_id ? String(raw.variant_id) : null,
            product_id: raw.product_id ? String(raw.product_id) : null,
            meta: typeof raw.meta === 'object' && raw.meta !== null ? /** @type {Record<string, unknown>} */ (raw.meta) : {},
        };
    }

    const variantId = raw.variant_uuid ?? raw.variant_id ?? null;
    const barcode = String(raw.barcode ?? raw.sku ?? '').trim();
    const variantName = String(raw.variant_name ?? '').trim();
    const source = variantId ? 'PRODUCT' : 'MANUAL';

    return {
        source,
        title: String(raw.title ?? raw.product_name ?? ''),
        barcode,
        display_text: source === 'MANUAL' && variantName !== '' ? variantName : barcode,
        owner_name: String(raw.owner_name ?? ''),
        quantity: Math.max(1, Number(raw.quantity) || 1),
        thumbnail_url: raw.thumbnail_url ? String(raw.thumbnail_url) : null,
        variant_id: variantId ? String(variantId) : null,
        product_id: raw.product_id ? String(raw.product_id) : null,
        meta: typeof raw.meta === 'object' && raw.meta !== null ? /** @type {Record<string, unknown>} */ (raw.meta) : {},
    };
}

/**
 * @param {QueueItem} item
 * @returns {Record<string, unknown>}
 */
export function queueItemToPayload(item) {
    return {
        source: item.source,
        title: item.title,
        barcode: item.barcode,
        display_text: item.display_text,
        owner_name: item.owner_name,
        quantity: item.quantity,
        thumbnail_url: item.thumbnail_url,
        variant_id: item.variant_id,
        product_id: item.product_id,
        meta: item.meta ?? {},
    };
}
