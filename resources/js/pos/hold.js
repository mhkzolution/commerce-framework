import { apiPost, routeUrl } from './api.js';
import { getState, setState, showToast } from './state.js';
import { renderAll, renderHolds } from './render.js';

export function initHold() {
    document.addEventListener('click', async (event) => {
        if (event.target.closest('[data-pos-action="hold"]')) {
            try {
                const data = await apiPost(routeUrl('hold'), {});
                setState(data);
                renderAll(data);
                showToast('Sale held.', false);
            } catch (error) {
                showToast(error.message);
            }
        }

        if (event.target.closest('[data-pos-action="resume"]')) {
            renderHolds(getState()?.holds || []);
            const dialog = document.getElementById('pos-hold-dialog');
            if (dialog) dialog.hidden = false;
        }

        if (event.target.closest('[data-pos-action="clear-cart"]')) {
            const { clearCart } = await import('./cart.js');
            await clearCart();
        }

        const resumeBtn = event.target.closest('[data-pos-resume-hold]');
        if (resumeBtn) {
            try {
                const data = await apiPost(routeUrl('resume', { __ID__: resumeBtn.dataset.posResumeHold }), {});
                setState(data);
                renderAll(data);
                const dialog = document.getElementById('pos-hold-dialog');
                if (dialog) dialog.hidden = true;
            } catch (error) {
                showToast(error.message);
            }
        }
    });
}
