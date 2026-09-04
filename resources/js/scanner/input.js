const SCAN_GAP_MS = 250;

let scanBuffer = '';
let lastScanKeyAt = 0;
let onSubmit = null;

export function focusScannerInput() {
    document.getElementById('warehouse-scanner-input')?.focus();
}

function normalizeSku(value) {
    return String(value || '')
        .replace(/[\x00-\x1F\x7F]/g, '')
        .trim();
}

function flashInput(type) {
    const input = document.getElementById('warehouse-scanner-input');
    if (!input) return;

    input.classList.remove('is-success', 'is-error');
    input.classList.add(type === 'error' ? 'is-error' : 'is-success');
    window.setTimeout(() => input.classList.remove('is-success', 'is-error'), 220);
}

function resetScanBuffer() {
    scanBuffer = '';
    lastScanKeyAt = 0;
}

function isOverlayOpen() {
    const overlay = document.querySelector('[data-scanner-shortcut-overlay]');
    return overlay && !overlay.hidden;
}

function shouldCaptureGlobally(target) {
    if (!target || isOverlayOpen()) return false;
    if (target.id === 'warehouse-scanner-input') return false;

    const blocked = [
        'textarea',
        'select',
        '#scanner-qty-input',
        'input',
    ];

    return !blocked.some((selector) => target.matches?.(selector));
}

export function initScannerInput(submitHandler) {
    onSubmit = submitHandler;
    const input = document.getElementById('warehouse-scanner-input');
    if (!input) return;

    input.addEventListener('keydown', async (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        resetScanBuffer();
        await handleSubmit(input.value);
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
                if (input instanceof HTMLInputElement) {
                    input.value = code;
                }
                void handleSubmit(code);
            }
            return;
        }

        if (event.key.length !== 1) return;

        const now = Date.now();
        if (lastScanKeyAt > 0 && now - lastScanKeyAt > SCAN_GAP_MS) {
            scanBuffer = '';
        }

        lastScanKeyAt = now;
        scanBuffer += event.key;
        event.preventDefault();
    }, true);
}

async function handleSubmit(sku) {
    const trimmed = normalizeSku(sku);
    if (trimmed === '' || !onSubmit) return;

    try {
        const ok = await onSubmit(trimmed);
        const input = document.getElementById('warehouse-scanner-input');
        if (input) input.value = '';
        flashInput(ok === false ? 'error' : 'success');
        focusScannerInput();
    } catch {
        flashInput('error');
        focusScannerInput();
    }
}

export { flashInput };
