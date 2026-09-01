import { apiPatch, apiPost, apiDelete, routeUrl } from './api.js';
import { setState, showToast } from './state.js';
import { renderAll } from './render.js';
import { enqueueAction, isOnline } from './offline.js';

export async function addToCart({ purchasableUuid, sku, quantity = 1 }) {
    const payload = {
        purchasable_uuid: purchasableUuid || undefined,
        sku: sku || undefined,
        quantity,
    };

    const offlineAction = { type: 'add_item', payload };

    if (!isOnline()) {
        enqueueAction(offlineAction.type, offlineAction.payload);
        showToast('Item queued for sync.', false);
        return null;
    }

    const data = await apiPost(routeUrl('addItem'), payload);
    setState(data);
    renderAll(data);
    return data;
}

export async function updateLineQuantity(purchasableUuid, quantity) {
    const data = await apiPatch(routeUrl('updateItem', { __UUID__: purchasableUuid }), { quantity });
    setState(data);
    renderAll(data);
    return data;
}

export async function removeLine(purchasableUuid) {
    const data = await apiDelete(routeUrl('removeItem', { __UUID__: purchasableUuid }));
    setState(data);
    renderAll(data);
    return data;
}

export async function setLinePrice(purchasableUuid, unitPriceMinor) {
    const payload = { unit_price_minor: unitPriceMinor };

    if (!isOnline()) {
        enqueueAction('set_line_price', { purchasable_uuid: purchasableUuid, ...payload });
        showToast('Price override queued.', false);
        return null;
    }

    const data = await apiPatch(routeUrl('setLinePrice', { __UUID__: purchasableUuid }), payload);
    setState(data);
    renderAll(data);
    return data;
}

export async function clearCart() {
    if (!confirm('Clear the entire cart?')) return;

    if (!isOnline()) {
        enqueueAction('clear_cart', {});
        showToast('Clear cart queued.', false);
        return null;
    }

    const data = await apiDelete(routeUrl('clearCart'));
    setState(data);
    renderAll(data);
    return data;
}

export function bindCartEvents(app) {
    app.addEventListener('click', async (event) => {
        const line = event.target.closest('[data-pos-cart-line]');
        if (!line) return;

        const uuid = line.dataset.lineId;
        const qtyInput = line.querySelector('[data-pos-qty-input]');
        const currentQty = parseInt(qtyInput?.value || '1', 10);

        try {
            if (event.target.closest('[data-pos-action="override-price"]')) {
                const current = line.querySelector('.pos-cart-line__pricing .text-xs')?.textContent?.replace(/,/g, '') || '';
                const input = prompt('Enter new unit price:', current);
                if (input === null) return;
                const minor = Math.round(parseFloat(input) * 100);
                if (Number.isNaN(minor) || minor < 0) {
                    showToast('Invalid price.');
                    return;
                }
                await setLinePrice(uuid, minor);
            } else if (event.target.closest('[data-pos-qty-increase]')) {
                await updateLineQuantity(uuid, currentQty + 1);
            } else if (event.target.closest('[data-pos-qty-decrease]')) {
                await updateLineQuantity(uuid, Math.max(0, currentQty - 1));
            } else if (event.target.closest('[data-pos-remove-line]')) {
                await removeLine(uuid);
            }
        } catch (error) {
            showToast(error.message);
        }
    });

    app.addEventListener('change', async (event) => {
        const input = event.target.closest('[data-pos-qty-input]');
        if (!input) return;
        const line = input.closest('[data-pos-cart-line]');
        if (!line) return;

        try {
            await updateLineQuantity(line.dataset.lineId, parseInt(input.value || '1', 10));
        } catch (error) {
            showToast(error.message);
        }
    });
}
