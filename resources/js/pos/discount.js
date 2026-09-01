import { apiPost, apiDelete, routeUrl } from './api.js';
import { setState, showToast } from './state.js';
import { renderAll } from './render.js';
import { enqueueAction, isOnline } from './offline.js';

export function initDiscount() {
    document.addEventListener('click', async (event) => {
        if (event.target.closest('[data-pos-action="apply-coupon"]')) {
            const input = document.getElementById('pos-discount-input');
            const code = input?.value.trim();
            if (!code) {
                showToast('Enter a coupon code.');
                return;
            }
            await applyCoupon(code);
        }

        if (event.target.closest('[data-pos-action="remove-coupon"]')) {
            await removeCoupon();
        }
    });
}

async function applyCoupon(code) {
    try {
        if (!isOnline()) {
            enqueueAction('apply_coupon', { code });
            showToast('Coupon queued for sync.', false);
            return;
        }
        const data = await apiPost(routeUrl('applyCoupon'), { code });
        setState(data);
        renderAll(data);
        showToast('Coupon applied.', false);
    } catch (error) {
        showToast(error.message);
    }
}

async function removeCoupon() {
    try {
        if (!isOnline()) {
            enqueueAction('remove_coupon', {});
            showToast('Coupon removal queued.', false);
            return;
        }
        const data = await apiDelete(routeUrl('removeCoupon'));
        setState(data);
        renderAll(data);
    } catch (error) {
        showToast(error.message);
    }
}
