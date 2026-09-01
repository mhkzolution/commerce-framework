import { addToCart } from './cart.js';
import { showToast } from './state.js';

const SCAN_GAP_MS = 100;

let scanBuffer = '';
let lastScanKeyAt = 0;

export function focusBarcodeInput() {
    document.getElementById('pos-barcode-input')?.focus();
}

async function submitBarcode(sku) {
    const trimmed = sku.trim();
    if (trimmed === '') return false;

    try {
        await addToCart({ sku: trimmed });
        const input = document.getElementById('pos-barcode-input');
        if (input) input.value = '';
        focusBarcodeInput();
        return true;
    } catch (error) {
        showToast(error.message);
        return false;
    }
}

function isDialogOpen() {
    return document.querySelector('[data-pos-dialog]:not([hidden])') !== null;
}

function shouldCaptureGlobally(target) {
    if (!target || isDialogOpen()) return false;
    if (target.id === 'pos-barcode-input') return false;

    const blockedSelectors = [
        'textarea',
        'select',
        '#pos-notes-input',
        '#pos-discount-input',
        '#pos-payment-amount',
        '#pos-customer-search-input',
        '#pos-search-input',
        'input',
    ];

    return !blockedSelectors.some((selector) => target.matches?.(selector));
}

function resetScanBuffer() {
    scanBuffer = '';
    lastScanKeyAt = 0;
}

export function initBarcode() {
    const input = document.getElementById('pos-barcode-input');
    if (!input) return;

    input.addEventListener('keydown', async (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        resetScanBuffer();
        await submitBarcode(input.value);
    });

    document.addEventListener('keydown', (event) => {
        if (!shouldCaptureGlobally(event.target)) {
            if (event.key === 'Enter') resetScanBuffer();
            return;
        }

        if (event.ctrlKey || event.metaKey || event.altKey) return;

        if (event.key === 'Enter') {
            if (scanBuffer.length > 0) {
                event.preventDefault();
                const code = scanBuffer;
                resetScanBuffer();
                void submitBarcode(code);
            }
            return;
        }

        if (event.key.length !== 1) return;

        const now = Date.now();
        if (lastScanKeyAt && now - lastScanKeyAt > SCAN_GAP_MS) {
            scanBuffer = '';
        }

        lastScanKeyAt = now;
        scanBuffer += event.key;
        event.preventDefault();
    }, true);
}
