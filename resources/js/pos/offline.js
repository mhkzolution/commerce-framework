const QUEUE_KEY = 'commerce.pos.offline_queue';
let flushInProgress = false;

export function initOffline() {
    window.addEventListener('online', () => flushQueue());
    updateSyncIndicator(navigator.onLine ? 'synced' : 'offline');

    if (navigator.onLine) {
        flushQueue();
    }
}

export function isOnline() {
    return navigator.onLine;
}

export function enqueueAction(type, payload = {}) {
    const queue = readQueue();
    const action = {
        id: `offline-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        type,
        payload,
        created_at: new Date().toISOString(),
    };
    queue.push(action);
    writeQueue(queue);
    updateSyncIndicator('pending');
    return action;
}

export function readQueue() {
    try {
        const raw = localStorage.getItem(QUEUE_KEY);
        return raw ? JSON.parse(raw) : [];
    } catch {
        return [];
    }
}

function writeQueue(queue) {
    localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
}

export async function flushQueue() {
    if (flushInProgress || !navigator.onLine) return;

    const queue = readQueue();
    if (queue.length === 0) {
        updateSyncIndicator('synced');
        return;
    }

    flushInProgress = true;
    updateSyncIndicator('syncing');

    try {
        const { apiPost, routeUrl } = await import('./api.js');
        const { setState } = await import('./state.js');
        const { renderAll } = await import('./render.js');

        const data = await apiPost(routeUrl('sync'), {
            actions: queue.map((item) => ({
                id: item.id,
                type: item.type,
                payload: item.payload,
            })),
        });

        writeQueue([]);
        setState(data);
        renderAll(data);
        updateSyncIndicator('synced');
    } catch {
        updateSyncIndicator('pending');
    } finally {
        flushInProgress = false;
    }
}

export function updateSyncIndicator(status) {
    document.querySelectorAll('.pos-topbar__status').forEach((el, index) => {
        if (index !== 1) return;
        const label = el.querySelector('span:last-child');
        const dot = el.querySelector('.pos-topbar__status-dot');
        if (!label || !dot) return;

        label.textContent = status === 'syncing' ? 'Syncing' : status === 'pending' ? 'Pending' : status === 'offline' ? 'Offline' : 'Synced';
        dot.classList.toggle('pos-topbar__status-dot--syncing', status === 'syncing' || status === 'pending');
        dot.classList.toggle('pos-topbar__status-dot--offline', status === 'offline');
    });
}
