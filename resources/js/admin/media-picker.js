function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function jsonHeaders() {
    return {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
    };
}

let host = null;
let searchTimer = null;

function ensureHost() {
    if (host) {
        return host;
    }

    host = document.createElement('div');
    host.className = 'cf-media-picker-overlay';
    host.hidden = true;
    host.innerHTML = `
        <div class="cf-media-picker" role="dialog" aria-modal="true" aria-label="Select media">
            <header class="cf-media-picker__header">
                <strong data-picker-title>Select media</strong>
                <button type="button" data-picker-dismiss>&times;</button>
            </header>
            <div class="cf-media-picker__toolbar">
                <input type="search" class="cf-input" placeholder="Search library…" data-picker-q>
                <button type="button" class="cf-media-chip is-active" data-picker-scope="all">All</button>
                <button type="button" class="cf-media-chip" data-picker-scope="recent">Recent</button>
            </div>
            <div class="cf-media-picker__body">
                <nav class="cf-media-picker__folders" data-picker-folders></nav>
                <div class="cf-media-picker__grid" data-picker-items></div>
            </div>
            <footer class="cf-media-picker__footer">
                <span data-picker-meta></span>
                <div>
                    <button type="button" class="cf-btn cf-btn--ghost" data-picker-dismiss>Cancel</button>
                    <button type="button" class="cf-btn cf-btn--primary" data-picker-confirm hidden>Use selected</button>
                </div>
            </footer>
        </div>
    `;
    document.body.appendChild(host);
    return host;
}

export function openMediaPicker({
    url,
    multiple = false,
    imagesOnly = true,
    title = 'Select media',
} = {}) {
    if (!url) {
        return Promise.resolve(multiple ? [] : null);
    }

    const overlay = ensureHost();
    const itemsEl = overlay.querySelector('[data-picker-items]');
    const foldersEl = overlay.querySelector('[data-picker-folders]');
    const searchEl = overlay.querySelector('[data-picker-q]');
    const metaEl = overlay.querySelector('[data-picker-meta]');
    const confirmBtn = overlay.querySelector('[data-picker-confirm]');
    const titleEl = overlay.querySelector('[data-picker-title]');

    const state = {
        folder: 'all',
        recent: false,
        search: '',
        selected: [],
        items: [],
    };

    titleEl.textContent = title;
    searchEl.value = '';
    confirmBtn.hidden = !multiple;
    overlay.hidden = false;

    const close = (result) => {
        overlay.hidden = true;
        cleanup();
        resolvePromise(multiple ? (result || []) : (result?.[0] || null));
    };

    let resolvePromise = () => {};
    const cleanupFns = [];
    const on = (node, type, fn) => {
        node.addEventListener(type, fn);
        cleanupFns.push(() => node.removeEventListener(type, fn));
    };
    const cleanup = () => cleanupFns.forEach((fn) => fn());

    const render = () => {
        foldersEl.querySelectorAll('[data-picker-folder]').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.pickerFolder === state.folder);
        });
        overlay.querySelectorAll('[data-picker-scope]').forEach((chip) => {
            const recent = chip.dataset.pickerScope === 'recent';
            chip.classList.toggle('is-active', recent === state.recent);
        });
        itemsEl.innerHTML = state.items.map((item) => {
            const selected = state.selected.includes(item.uuid);
            const thumb = item.preview_url || item.url;
            const isImage = (item.mime_type || '').startsWith('image/');
            const body = isImage && thumb
                ? `<img src="${escapeHtml(thumb)}" alt="${escapeHtml(item.original_filename || '')}">`
                : `<div class="cf-media-picker__file">${escapeHtml((item.media_type || 'file').toUpperCase())}</div>`;
            return `
                <button type="button" class="cf-media-picker__tile ${selected ? 'is-selected' : ''}" data-picker-item="${escapeHtml(item.uuid)}">
                    ${body}
                    <span>${escapeHtml(item.original_filename || item.filename || '')}</span>
                </button>
            `;
        }).join('');
        confirmBtn.textContent = state.selected.length
            ? `Use selected (${state.selected.length})`
            : 'Use selected';
    };

    const load = async () => {
        const params = new URLSearchParams({
            images_only: imagesOnly ? '1' : '0',
            page: '1',
            search: state.search,
        });
        if (state.folder && state.folder !== 'all') params.set('folder', state.folder);
        if (state.recent) params.set('recent', '1');
        const response = await fetch(`${url}?${params.toString()}`, { headers: jsonHeaders() });
        const payload = await response.json();
        state.items = payload.data || [];
        const folders = payload.folders || [];
        foldersEl.innerHTML = [
            `<button type="button" data-picker-folder="all">All folders</button>`,
            `<button type="button" data-picker-folder="unfiled">Unfiled</button>`,
            ...folders.map((folder) => `<button type="button" data-picker-folder="${escapeHtml(folder.uuid)}">${escapeHtml(folder.name)}</button>`),
        ].join('');
        metaEl.textContent = `${payload.meta?.total ?? state.items.length} items`;
        render();
    };

    on(overlay, 'click', (event) => {
        if (event.target === overlay || event.target.closest('[data-picker-dismiss]')) {
            event.preventDefault();
            close(multiple ? [] : null);
        }
    });

    on(overlay, 'click', (event) => {
        const folder = event.target.closest('[data-picker-folder]');
        if (folder) {
            state.folder = folder.dataset.pickerFolder;
            state.recent = false;
            load();
            return;
        }
        const scope = event.target.closest('[data-picker-scope]');
        if (scope) {
            state.recent = scope.dataset.pickerScope === 'recent';
            if (state.recent) state.folder = 'all';
            load();
            return;
        }
        const tile = event.target.closest('[data-picker-item]');
        if (!tile) return;
        const uuid = tile.dataset.pickerItem;
        if (multiple) {
            state.selected = state.selected.includes(uuid)
                ? state.selected.filter((id) => id !== uuid)
                : [...state.selected, uuid];
            render();
            return;
        }
        const item = state.items.find((entry) => entry.uuid === uuid);
        close(item ? [item] : []);
    });

    on(confirmBtn, 'click', () => {
        const picked = state.items.filter((item) => state.selected.includes(item.uuid));
        close(picked);
    });

    on(searchEl, 'input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => {
            state.search = searchEl.value.trim();
            load();
        }, 200);
    });

    load();

    return new Promise((resolve) => {
        resolvePromise = resolve;
    });
}

function previewMarkup(url) {
    return url
        ? `<img src="${escapeHtml(url)}" alt="Selected media" class="h-full w-full object-cover">`
        : '<span class="text-xs text-muted">No image</span>';
}

export function initMediaPickers(root = document) {
    root.querySelectorAll('[data-picker-root]').forEach((node) => {
        if (node.dataset.ready === '1') return;
        node.dataset.ready = '1';

        const input = node.querySelector('[data-picker-input]');
        const preview = node.querySelector('[data-picker-preview]');
        const url = node.dataset.pickerUrl;
        const multiple = node.dataset.pickerMultiple === '1';

        node.querySelector('[data-picker-open]')?.addEventListener('click', async () => {
            const picked = await openMediaPicker({
                url,
                multiple,
                imagesOnly: node.dataset.pickerImages !== '0',
            });
            const items = Array.isArray(picked) ? picked : (picked ? [picked] : []);
            if (!items.length) return;
            if (multiple) {
                input.value = items.map((item) => item.uuid).join(',');
            } else {
                input.value = items[0].uuid;
            }
            preview.innerHTML = previewMarkup(items[0].preview_url || items[0].url);
        });

        node.querySelector('[data-picker-clear]')?.addEventListener('click', () => {
            input.value = '';
            preview.innerHTML = previewMarkup(null);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => initMediaPickers());
