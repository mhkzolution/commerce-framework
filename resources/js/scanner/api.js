let config = null;
let initial = null;

export function initConfig(root) {
    config = JSON.parse(root.dataset.scannerConfig || '{}');
    initial = JSON.parse(root.dataset.scannerInitial || '{}');
}

export function getConfig() {
    return config || {};
}

export function getInitial() {
    return initial || {};
}

export function getRoutes() {
    return getConfig().routes || {};
}

export function getPermissions() {
    return getConfig().permissions || {};
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function request(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...(options.headers || {}),
        },
        ...options,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || 'Request failed');
    }

    return data;
}

export async function lookupSku(sku) {
    const response = await fetch(getRoutes().lookup, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ sku }),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        return {
            found: false,
            sku,
            message: data.message || 'SKU not found',
        };
    }

    return data;
}

export async function recordScan(payload) {
    return request(getRoutes().scan, {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

export async function fetchHistory(limit = 10) {
    const url = new URL(getRoutes().history, window.location.origin);
    url.searchParams.set('limit', String(limit));
    return request(url.toString());
}

export async function fetchDashboard() {
    return request(getRoutes().dashboard);
}
