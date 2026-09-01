import { initConfig, getConfig, getInitial, lookupSku, recordScan, fetchHistory, fetchDashboard } from './api.js';
import { getState, setState, resetProduct, setQuantity } from './state.js';
import { initScannerInput, focusScannerInput } from './input.js';
import { initKeyboard } from './keyboard.js';
import {
    renderAll,
    renderHistory,
    showToast,
    updateTodayScans,
    bindUiEvents,
    initClock,
} from './render.js';
import { getPrimaryAction } from './modes.js';

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('warehouse-scanner-app');
    if (!app) return;

    initConfig(app);

    const initial = getInitial();
    const mockPick = getConfig().mockPickOrder || {};

    setState({
        mode: initial.mode || 'stock-check',
        history: initial.recentScans || [],
        pickLines: mockPick.lines || [],
        packLines: mockPick.lines || [],
    });

    renderAll();
    initClock();
    initScannerInput(handleScan);
    bindUiEvents({
        onModeChange: changeMode,
        onAction: handleAction,
    });
    initKeyboard({
        onModeChange: changeMode,
        onAction: handleAction,
    });

    focusScannerInput();

    try {
        const stored = sessionStorage.getItem('warehouse-scanner-mode');
        if (stored) changeMode(stored, false);
    } catch {
        // ignore
    }
});

async function handleScan(sku) {
    try {
        const result = await lookupSku(sku);
        if (!result.found) {
            showToast(result.message || 'SKU not found', 'error');
            resetProduct();
            setState({ sku });
            renderAll();
            return false;
        }

        setState({
            product: result.product,
            sku,
            quantity: 1,
            step: 'loaded',
        });
        renderAll();
        return true;
    } catch (error) {
        showToast(error.message, 'error');
        return false;
    }
}

async function handleAction(action) {
    const { mode, product, sku, quantity } = getState();

    if (action === 'view_product') {
        if (product?.product_url) {
            window.open(product.product_url, '_blank');
        }
        focusScannerInput();
        return;
    }

    if (action === 'skip') {
        completeAction('skip');
        return;
    }

    if (!product && action !== 'skip') {
        showToast('Scan a product first', 'error');
        focusScannerInput();
        return;
    }

    const primary = getPrimaryAction(mode);
    const recordable = ['found', 'not_found', 'damaged', 'wrong_location', 'move', 'attached', 'receive', 'correct', 'wrong_item', 'pack', 'complete', 'transfer', 'save', 'adjust_stock'];

    if (!recordable.includes(action)) {
        focusScannerInput();
        return;
    }

    const destination = document.querySelector('[data-scanner-destination]')?.value;

    try {
        await recordScan({
            mode,
            sku: product?.sku || sku,
            action,
            variant_uuid: product?.variant_uuid || null,
            quantity: ['receive', 'transfer', 'save'].includes(action) ? quantity : null,
            meta: destination ? { destination } : {},
        });

        await refreshStats();
        completeAction(action);
    } catch (error) {
        showToast(error.message, 'error');
        focusScannerInput();
    }
}

function completeAction(action) {
    const message = action === 'skip' ? 'Skipped' : 'Action recorded';
    showToast(message, 'success');
    resetProduct();
    renderAll();
    refreshHistory();
    focusScannerInput();
}

function changeMode(mode, persist = true) {
    setState({ mode, quantity: 1 });
    renderAll();
    if (persist) {
        try {
            sessionStorage.setItem('warehouse-scanner-mode', mode);
        } catch {
            // ignore
        }
    }
    focusScannerInput();
}

async function refreshHistory() {
    try {
        const data = await fetchHistory();
        setState({ history: data.scans || [] });
        renderHistory();
    } catch {
        // ignore
    }
}

async function refreshStats() {
    try {
        const data = await fetchDashboard();
        updateTodayScans(data.stats?.total_scans || 0);
    } catch {
        // ignore
    }
}
