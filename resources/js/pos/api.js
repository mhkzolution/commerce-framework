let config = { routes: {}, initial: null };

export function initConfig(app) {
    try {
        config = JSON.parse(app.dataset.posConfig || '{}');
    } catch {
        config = { routes: {}, initial: null };
    }
}

export function getRoutes() {
    return config.routes;
}

export function getInitialState() {
    return config.initial;
}

export function routeUrl(key, replacements = {}) {
    let url = config.routes[key] || '';
    Object.entries(replacements).forEach(([token, value]) => {
        url = url.replace(token, encodeURIComponent(value));
    });
    return url;
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export async function apiRequest(url, options = {}) {
    if (!navigator.onLine && !options.allowOffline) {
        const { enqueueAction, updateSyncIndicator } = await import('./offline.js');
        if (options.offlineAction) {
            enqueueAction(options.offlineAction.type, options.offlineAction.payload || {});
            updateSyncIndicator('pending');
            throw new Error('Offline — action queued for sync.');
        }
        throw new Error('You are offline.');
    }

    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {}),
        },
        credentials: 'same-origin',
        ...options,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const firstValidationError = data.errors
            ? Object.values(data.errors).flat().find(Boolean)
            : null;

        throw new Error(firstValidationError || data.message || 'Request failed.');
    }

    return data;
}

export async function apiGet(url) {
    return apiRequest(url, { method: 'GET', allowOffline: true });
}

export async function apiPost(url, body = {}, offlineAction = null) {
    return apiRequest(url, {
        method: 'POST',
        body: JSON.stringify(body),
        offlineAction,
    });
}

export async function apiPatch(url, body = {}, offlineAction = null) {
    return apiRequest(url, {
        method: 'PATCH',
        body: JSON.stringify(body),
        offlineAction,
    });
}

export async function apiDelete(url, offlineAction = null) {
    return apiRequest(url, { method: 'DELETE', offlineAction });
}
