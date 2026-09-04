import { getState, setState, setQuantity } from './state.js';
import { getModeActions, getModeLabel, modeNeedsQty } from './modes.js';
import { getConfig, getInitial } from './api.js';
import { focusScannerInput } from './input.js';

const STATUS_LABELS = {
    in_stock: 'In stock',
    low: 'Low stock',
    out: 'Out of stock',
    archived: 'Archived',
    damaged: 'Damaged',
    unknown: 'Unknown',
};

function el(selector) {
    return document.querySelector(selector);
}

function formatStock(product) {
    if (!product) return '—';
    return `${product.on_hand} on hand · ${product.available} available`;
}

function formatLocation(product) {
    if (!product?.location) return '—';
    return `${product.location.name} (${product.location.code})`;
}

export function renderAll() {
    renderMode();
    renderPreview();
    renderActions();
    renderQtyPanel();
    renderHistory();
}

export function renderMode() {
    const { mode } = getState();
    document.querySelectorAll('[data-scanner-mode]').forEach((button) => {
        button.classList.toggle('is-active', button.dataset.scannerMode === mode);
    });

    const select = el('[data-scanner-mode-select]');
    if (select) select.value = mode;

    const title = el('[data-scanner-mode-title]');
    if (title) title.textContent = getModeLabel(mode);
}

export function renderPreview() {
    const { product } = getState();
    const empty = el('[data-scanner-preview-empty]');
    const content = el('[data-scanner-preview-content]');

    if (!product) {
        empty?.classList.remove('hidden');
        content?.classList.add('hidden');
        if (content) content.hidden = true;
        return;
    }

    empty?.classList.add('hidden');
    content?.classList.remove('hidden');
    if (content) content.hidden = false;

    const image = el('[data-scanner-preview-image]');
    const fallback = el('[data-scanner-preview-fallback]');

    if (product.thumbnail_url) {
        if (image) {
            image.src = product.thumbnail_url;
            image.alt = product.product_name;
            image.classList.remove('hidden');
        }
        fallback?.classList.add('hidden');
    } else {
        if (image) image.classList.add('hidden');
        fallback?.classList.remove('hidden');
    }

    setText('[data-scanner-preview-name]', product.product_name);
    setText('[data-scanner-preview-variant]', product.variant_name || '—');
    setText('[data-scanner-preview-owner]', product.owner_name || '—');
    setText('[data-scanner-preview-sku]', product.sku);
    setText('[data-scanner-preview-stock]', formatStock(product));
    setText('[data-scanner-preview-location]', formatLocation(product));
    setText('[data-scanner-preview-shelf]', product.shelf || '—');

    const status = el('[data-scanner-preview-status]');
    if (status) {
        status.textContent = STATUS_LABELS[product.status] || product.status;
        status.dataset.status = product.status;
    }

    renderModeContext();
}

function renderModeContext() {
    const { mode, product, quantity, pickLines } = getState();
    const context = el('[data-scanner-mode-context]');
    if (!context) return;

    let html = '';

    if (mode === 'picking') {
        const match = pickLines.find((line) => line.sku === product?.sku);
        html = match
            ? `<strong>Pick list:</strong> ${match.name}<br>Expected qty: ${match.quantity}`
            : '<strong>Warning:</strong> SKU not on current pick list';
    }

    if (mode === 'packing') {
        html = '<strong>Pack verification</strong><br>Scan each item before completing shipment.';
    }

    if (mode === 'inventory-count' && product) {
        const variance = quantity - product.on_hand;
        html = `Expected: <strong>${product.on_hand}</strong> · Counted: <strong>${quantity}</strong> · Variance: <strong>${variance >= 0 ? '+' : ''}${variance}</strong>`;
    }

    if (mode === 'transfer') {
        const locations = getConfig().locations || [];
        const options = locations.map((loc) => `<option value="${loc.code}">${loc.name}</option>`).join('');
        html = `<label for="scanner-destination">Destination</label><select id="scanner-destination" class="scanner-mode-switcher__select" data-scanner-destination>${options}</select>`;
    }

    if (html) {
        context.innerHTML = html;
        context.classList.remove('hidden');
        context.hidden = false;
    } else {
        context.innerHTML = '';
        context.classList.add('hidden');
        context.hidden = true;
    }
}

export function renderActions() {
    const { mode, product } = getState();
    const grid = el('[data-scanner-actions]');
    if (!grid) return;

    grid.innerHTML = '';
    const actions = getModeActions(mode);

    actions.forEach((action) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `scanner-quick-action scanner-quick-action--${action.variant || 'secondary'}`;
        button.dataset.scannerAction = action.action;
        if (action.hotkey) button.dataset.scannerHotkey = action.hotkey;
        button.disabled = !product && action.action !== 'skip';

        if (action.hotkey) {
            const key = document.createElement('span');
            key.className = 'scanner-quick-action__key';
            key.innerHTML = `<kbd>${action.hotkey}</kbd>`;
            button.appendChild(key);
        }

        const label = document.createElement('span');
        label.className = 'scanner-quick-action__label';
        label.textContent = action.labelKey.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
        button.appendChild(label);

        grid.appendChild(button);
    });
}

export function renderQtyPanel() {
    const { mode, product, quantity } = getState();
    const panel = el('[data-scanner-qty-panel]');
    const input = el('[data-scanner-qty-input]');
    const show = Boolean(product) && modeNeedsQty(mode);

    if (panel) {
        panel.classList.toggle('hidden', !show);
        panel.hidden = !show;
    }

    if (input && show) {
        input.value = String(quantity);
    }
}

export function renderHistory(scans = null) {
    const rows = el('[data-scanner-history-rows]');
    if (!rows) return;

    const list = scans || getState().history || getInitial().recentScans || [];
    rows.innerHTML = '';

    if (!list.length) {
        rows.innerHTML = '<tr data-scanner-history-empty><td colspan="5" class="scanner-history__empty">—</td></tr>';
        return;
    }

    list.forEach((scan) => {
        const tr = document.createElement('tr');
        const time = scan.created_at ? new Date(scan.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—';
        tr.innerHTML = `
            <td>${time}</td>
            <td>${escapeHtml(scan.staff || '—')}</td>
            <td>${escapeHtml((scan.mode || '').replace(/-/g, ' '))}</td>
            <td><code>${escapeHtml(scan.sku || '—')}</code></td>
            <td>${escapeHtml((scan.action || '—').replace(/_/g, ' '))}</td>
        `;
        rows.appendChild(tr);
    });
}

export function showToast(message, type = 'success') {
    const toast = el('[data-scanner-toast]');
    if (!toast) return;

    toast.textContent = message;
    toast.classList.remove('hidden', 'is-error', 'is-success');
    toast.classList.add(type === 'error' ? 'is-error' : 'is-success');
    window.clearTimeout(showToast._timer);
    showToast._timer = window.setTimeout(() => toast.classList.add('hidden'), 2800);
}

export function updateTodayScans(count) {
    const node = el('[data-scanner-today-scans]');
    if (node) node.textContent = `Today: ${count} scans`;
}

function setText(selector, value) {
    const node = el(selector);
    if (node) node.textContent = value;
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

export function bindUiEvents({ onAction, onModeChange }) {
    document.querySelectorAll('[data-scanner-mode]').forEach((button) => {
        button.addEventListener('click', () => onModeChange(button.dataset.scannerMode));
    });

    const select = el('[data-scanner-mode-select]');
    select?.addEventListener('change', (event) => onModeChange(event.target.value));

    document.addEventListener('click', (event) => {
        const actionBtn = event.target.closest('[data-scanner-action]');
        if (actionBtn) {
            onAction(actionBtn.dataset.scannerAction);
            return;
        }

        if (event.target.matches('[data-scanner-qty-inc]')) {
            setQuantity(getState().quantity + 1);
            renderQtyPanel();
            renderModeContext();
        }

        if (event.target.matches('[data-scanner-qty-dec]')) {
            setQuantity(getState().quantity - 1);
            renderQtyPanel();
            renderModeContext();
        }

        if (event.target.matches('[data-scanner-close-shortcuts]')) {
            toggleShortcutOverlay(false);
        }

        if (event.target.matches('[data-scanner-history-toggle]')) {
            const body = el('[data-scanner-history-body]');
            if (body) {
                const open = body.hidden;
                body.hidden = !open;
                event.target.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
        }
    });

    const qtyInput = el('[data-scanner-qty-input]');
    qtyInput?.addEventListener('input', (event) => {
        setQuantity(event.target.value);
        renderModeContext();
    });

    document.addEventListener('input', (event) => {
        if (event.target.matches('[data-scanner-qty-input]')) {
            setQuantity(event.target.value);
            renderModeContext();
        }
    });
}

export function toggleShortcutOverlay(open) {
    const overlay = el('[data-scanner-shortcut-overlay]');
    if (!overlay) return;
    overlay.hidden = !open;
    overlay.classList.toggle('hidden', !open);
    if (!open) focusScannerInput();
}

export function initClock() {
    const clock = el('[data-scanner-clock]');
    if (!clock) return;

    const tick = () => {
        clock.textContent = new Date().toLocaleString('th-TH', {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });
    };

    tick();
    window.setInterval(tick, 30000);
}
