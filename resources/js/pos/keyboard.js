import { closeAllDialogs, openDialog } from './dialogs.js';
import { openPaymentDialog } from './checkout.js';
import { getState } from './state.js';
import { renderHolds } from './render.js';

const SHORTCUTS = {
    F1: 'pos:focus-search',
    F2: 'pos:open-customer',
    F3: 'pos:open-discount',
    F4: 'pos:open-payment',
    F5: 'pos:hold-sale',
    F6: 'pos:resume-sale',
    F9: 'pos:checkout',
    Escape: 'pos:cancel-dialog',
};

const CTRL_SHORTCUTS = {
    f: 'pos:focus-search',
    b: 'pos:focus-barcode',
    Delete: 'pos:clear-cart',
};

export function initKeyboard({ clearCart }) {
    document.addEventListener('keydown', (event) => {
        const inInput = event.target.matches('input, textarea, select');

        if ((event.ctrlKey || event.metaKey) && event.key === 'Delete') {
            event.preventDefault();
            clearCart();
            return;
        }

        if (inInput && !event.ctrlKey && !event.metaKey) {
            if (event.key === 'Escape') closeAllDialogs();
            return;
        }

        if (event.ctrlKey || event.metaKey) {
            const key = event.key.length === 1 ? event.key.toLowerCase() : event.key;
            const action = CTRL_SHORTCUTS[key];
            if (action) {
                event.preventDefault();
                handleAction(action, { clearCart });
            }
            return;
        }

        const action = SHORTCUTS[event.key];
        if (action) {
            event.preventDefault();
            handleAction(action, { clearCart });
        }
    });
}

function handleAction(action, { clearCart }) {
    switch (action) {
        case 'pos:focus-search':
            document.getElementById('pos-search-input')?.focus();
            break;
        case 'pos:focus-barcode':
            document.getElementById('pos-barcode-input')?.focus();
            break;
        case 'pos:open-customer':
            openDialog('pos-customer-dialog');
            break;
        case 'pos:open-discount':
            document.getElementById('pos-discount-input')?.focus();
            break;
        case 'pos:open-payment':
            openPaymentDialog();
            break;
        case 'pos:hold-sale':
            document.querySelector('[data-pos-action="hold"]')?.click();
            break;
        case 'pos:resume-sale': {
            const state = getState();
            renderHolds(state?.holds || []);
            openDialog('pos-hold-dialog');
            break;
        }
        case 'pos:checkout':
            document.querySelector('[data-pos-action="checkout"]')?.click();
            break;
        case 'pos:clear-cart':
            clearCart();
            break;
        case 'pos:cancel-dialog':
            closeAllDialogs();
            break;
        default:
            break;
    }
}
