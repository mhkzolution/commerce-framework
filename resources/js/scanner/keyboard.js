import { focusScannerInput } from './input.js';
import { getState } from './state.js';
import { getModeActions, getPrimaryAction } from './modes.js';
import { toggleShortcutOverlay } from './render.js';

const MODE_KEYS = {
    F1: 'stock-check',
    F2: 'label-attachment',
    F3: 'receiving',
    F4: 'picking',
    F5: 'packing',
    F6: 'transfer',
    F7: 'inventory-count',
};

export function initKeyboard({ onModeChange, onAction, onPrimary }) {
    document.addEventListener('keydown', (event) => {
        const inQty = event.target.id === 'scanner-qty-input';
        const inEditableField = event.target.matches('input, textarea, select');

        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'b') {
            event.preventDefault();
            focusScannerInput();
            return;
        }

        if (event.key === '?' && !inEditableField) {
            event.preventDefault();
            toggleShortcutOverlay(true);
            return;
        }

        if (event.key === 'Escape') {
            toggleShortcutOverlay(false);
            if (!inQty) focusScannerInput();
            return;
        }

        if (MODE_KEYS[event.key] && !inEditableField) {
            event.preventDefault();
            onModeChange(MODE_KEYS[event.key]);
            return;
        }

        if (event.key === 'F8' && !inEditableField) {
            event.preventDefault();
            const app = document.getElementById('warehouse-scanner-app');
            const routes = JSON.parse(app?.dataset.scannerConfig || '{}').routes || {};
            if (routes.dashboardPage) window.location.href = routes.dashboardPage;
            return;
        }

        // Do not steal number keys while the user is typing in any field (including SKU).
        if (/^[1-9]$/.test(event.key) && !inEditableField) {
            const { mode } = getState();
            const action = getModeActions(mode).find((item) => item.hotkey === event.key);
            if (action) {
                event.preventDefault();
                onAction(action.action);
            }
            return;
        }

        if (event.key === 'Enter' && inQty) {
            event.preventDefault();
            const primary = getPrimaryAction(getState().mode);
            if (primary) onAction(primary.action);
            return;
        }

        if ((event.key === '+' || event.key === '=') && !inEditableField) {
            const qty = document.querySelector('[data-scanner-qty-input]');
            if (qty && !qty.hidden) {
                event.preventDefault();
                qty.stepUp();
                qty.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }

        if (event.key === '-' && !inEditableField) {
            const qty = document.querySelector('[data-scanner-qty-input]');
            if (qty && !qty.hidden) {
                event.preventDefault();
                qty.stepDown();
                qty.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
    });
}
