const CONCURRENCY = 3;
const FOLDER_STORAGE = 'cf.media.foldersCollapsed';
const WIDTH_STORAGE = 'cf.media.folderWidth';

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

function formatBytes(bytes) {
    const size = Number(bytes) || 0;
    if (size < 1024) return `${size} B`;
    return `${(size / 1024).toFixed(1)} KB`;
}

function jsonHeaders() {
    return {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
    };
}

export function initMediaLibrary(root) {
    if (!root || root.dataset.ready === '1') {
        return;
    }
    root.dataset.ready = '1';

    const state = {
        folder: root.dataset.folder || 'all',
        type: root.dataset.type || '',
        period: root.dataset.period || '',
        search: root.dataset.search || '',
        page: Number(root.dataset.page || 1),
        lastPage: Number(root.dataset.lastPage || 1),
        total: Number(root.dataset.total || 0),
        loading: false,
        selected: [],
        lastIndex: null,
        focusIndex: null,
        queue: [],
        active: 0,
        drawerUuid: null,
        drawerUrl: null,
        drawerAlt: '',
        drawerFilename: '',
    };

    const grid = root.querySelector('[data-media-grid]');
    const sentinel = root.querySelector('[data-infinite-sentinel]');
    const status = root.querySelector('[data-infinite-status]');
    const searchInput = root.querySelector('[data-library-search]');
    const uploadInput = root.querySelector('[data-upload-input]');
    const stage = root.querySelector('[data-media-stage]');
    const overlay = root.querySelector('[data-drop-overlay]');
    const bulkBar = root.querySelector('[data-bulk-bar]');
    const bulkCount = root.querySelector('[data-bulk-count]');
    const drawer = root.querySelector('[data-media-drawer]');
    const queueEl = root.querySelector('[data-upload-queue]');
    const canUpload = root.dataset.canUpload === '1';
    const canUpdate = root.dataset.canUpdate === '1';
    const canDelete = root.dataset.canDelete === '1';
    const labels = {
        selected: (count) => `${count} selected`,
        empty: 'No files yet. Upload or import to get started.',
        emptyFiltered: 'No files match these filters.',
        loadMore: 'Loading more files…',
        end: 'End of library',
        uploading: 'Uploading',
        completed: 'completed',
        failed: 'failed',
        retry: 'Retry',
        deleteOne: 'Delete this file?',
        deleteMany: 'Delete selected files?',
        copied: 'Copied',
        openDetails: 'Open details',
        download: 'Download',
        delete: 'Delete',
        select: 'Select',
        selectAll: 'Select all :count loaded files',
    };

    const tiles = () => [...grid.querySelectorAll('[data-media-tile]')];

    function browseParams(page) {
        const params = new URLSearchParams();
        if (state.folder && state.folder !== 'all') params.set('folder', state.folder);
        if (state.type) params.set('type', state.type);
        if (state.period) params.set('period', state.period);
        if (state.search) params.set('search', state.search);
        params.set('page', String(page));
        params.set('per_page', '24');
        return params;
    }

    function syncUrl() {
        const url = new URL(window.location.href);
        url.search = browseParams(1).toString();
        url.searchParams.delete('page');
        url.searchParams.delete('per_page');
        window.history.replaceState({}, '', url);
        root.dataset.folder = state.folder;
        root.querySelectorAll('[data-folder-link]').forEach((link) => {
            link.classList.toggle('is-active', link.dataset.folder === state.folder);
        });
        root.querySelectorAll('[data-type-filter]').forEach((chip) => {
            chip.classList.toggle('is-active', (chip.dataset.typeFilter || '') === state.type);
        });
    }

    function tileHtml(item) {
        const isImage = (item.mime_type || '').startsWith('image/');
        const thumb = item.preview_url || item.url;
        const body = isImage && thumb
            ? `<img src="${escapeHtml(thumb)}" alt="${escapeHtml(item.alt_text || item.original_filename)}" loading="lazy" decoding="async">`
            : `<div class="cf-media-tile__file"><span>${escapeHtml((item.media_type || 'file').toUpperCase())}</span></div>`;
        const dims = item.width && item.height ? ` · ${item.width}×${item.height}` : '';
        return `
            <article class="cf-media-tile" data-media-tile data-uuid="${escapeHtml(item.uuid)}" data-url="${escapeHtml(item.url || '')}" data-alt="${escapeHtml(item.alt_text || item.original_filename || '')}" data-filename="${escapeHtml(item.original_filename || '')}" tabindex="0">
                <div class="cf-media-tile__thumb">
                    ${body}
                    <div class="cf-media-tile__overlay" aria-hidden="true"></div>
                    <button type="button" class="cf-media-tile__badge" data-tile-check aria-label="${labels.select}"></button>
                </div>
                <button type="button" class="cf-media-tile__more" data-open-details aria-label="${labels.openDetails}">⋯</button>
                <p class="cf-media-tile__name" title="${escapeHtml(item.original_filename)}">${escapeHtml(item.original_filename)}</p>
                <p class="cf-media-tile__meta">${formatBytes(item.size)}${dims}</p>
            </article>
        `;
    }

    function isSelected(uuid) {
        return state.selected.includes(uuid);
    }

    function clearSelected() {
        state.selected = [];
    }

    function addSelected(uuid) {
        if (!isSelected(uuid)) {
            state.selected.push(uuid);
        }
    }

    function paintSelection() {
        tiles().forEach((tile, index) => {
            const order = state.selected.indexOf(tile.dataset.uuid);
            const on = order >= 0;
            tile.classList.toggle('is-selected', on);
            tile.classList.toggle('is-focused', state.focusIndex === index);
            const badge = tile.querySelector('[data-tile-check]');
            if (badge) badge.textContent = on ? String(order + 1) : '';
            tile.dataset.index = String(index);
        });
        const count = state.selected.length;
        if (bulkBar) {
            bulkBar.classList.toggle('is-idle', count === 0);
            if (bulkCount) bulkCount.textContent = labels.selected(count);
            bulkBar.querySelectorAll('[data-bulk-action]').forEach((control) => {
                control.disabled = count === 0;
            });
            const selectAll = bulkBar.querySelector('[data-select-all]');
            if (selectAll) {
                const loaded = tiles().length;
                const template = selectAll.dataset.selectAllTemplate || labels.selectAll;
                selectAll.textContent = template.replace(':count', String(loaded));
                selectAll.disabled = loaded === 0 || count === loaded;
            }
        }
    }

    function selectTile(uuid, { additive = false, range = false, index = null } = {}) {
        if (range && state.lastIndex !== null && index !== null) {
            const list = tiles();
            const [start, end] = state.lastIndex < index
                ? [state.lastIndex, index]
                : [index, state.lastIndex];
            for (let i = start; i <= end; i += 1) {
                const id = list[i]?.dataset.uuid;
                if (id) addSelected(id);
            }
        } else if (additive) {
            if (isSelected(uuid)) {
                state.selected = state.selected.filter((id) => id !== uuid);
            } else {
                addSelected(uuid);
            }
        } else {
            state.selected = [uuid];
        }
        if (index !== null) {
            state.lastIndex = index;
            state.focusIndex = index;
            tiles()[index]?.focus({ preventScroll: true });
        }
        paintSelection();
    }

    async function load({ reset = false } = {}) {
        if (state.loading) return;
        if (!reset && state.page >= state.lastPage) return;
        state.loading = true;
        const page = reset ? 1 : state.page + 1;
        if (status) status.textContent = labels.loadMore;
        try {
            const response = await fetch(`${root.dataset.browseUrl}?${browseParams(page)}`, {
                headers: jsonHeaders(),
            });
            const payload = await response.json();
            const items = payload.data || [];
            const meta = payload.meta || {};
            state.page = meta.current_page || page;
            state.lastPage = meta.last_page || 1;
            state.total = meta.total || items.length;
            if (reset) {
                clearSelected();
                grid.innerHTML = items.length
                    ? items.map(tileHtml).join('')
                    : `<p class="cf-media-empty">${escapeHtml(state.search || state.type || state.period ? labels.emptyFiltered : labels.empty)}</p>`;
            } else {
                grid.querySelector('[data-empty-state]')?.remove();
                grid.insertAdjacentHTML('beforeend', items.map(tileHtml).join(''));
            }
            if (status) {
                status.textContent = state.page >= state.lastPage && (reset ? items.length : true)
                    ? (grid.querySelector('[data-media-tile]') ? labels.end : '')
                    : labels.loadMore;
            }
            if (sentinel) sentinel.hidden = state.page >= state.lastPage;
            paintSelection();
        } finally {
            state.loading = false;
        }
    }

    function resetAndLoad() {
        syncUrl();
        load({ reset: true });
    }

    async function copyText(text) {
        if (!text) return;
        await navigator.clipboard.writeText(text);
    }

    function openDrawer(uuid) {
        state.drawerUuid = uuid;
        fetch(`${root.dataset.showUrl}/${uuid}`, { headers: jsonHeaders() })
            .then((res) => res.json())
            .then((payload) => {
                const item = payload.data;
                if (!item) return;
                state.drawerUrl = item.url;
                state.drawerAlt = item.alt_text || item.original_filename || '';
                state.drawerFilename = item.original_filename || '';
                const preview = drawer.querySelector('[data-drawer-preview]');
                const isImage = (item.mime_type || '').startsWith('image/');
                preview.innerHTML = isImage && item.url
                    ? `<img src="${escapeHtml(item.url)}" alt="${escapeHtml(item.alt_text || item.original_filename)}">`
                    : `<div class="cf-media-tile__file">${escapeHtml(item.mime_type || '')}</div>`;
                drawer.querySelector('[data-drawer-filename]').textContent = item.original_filename || '';
                drawer.querySelector('[data-drawer-uuid]').textContent = item.uuid || '';
                drawer.querySelector('[data-drawer-mime]').textContent = item.mime_type || '';
                drawer.querySelector('[data-drawer-size]').textContent = formatBytes(item.size);
                drawer.querySelector('[data-drawer-dimensions]').textContent = item.width && item.height
                    ? `${item.width} × ${item.height}`
                    : '—';
                drawer.querySelector('[data-drawer-created]').textContent = item.created_at
                    ? new Date(item.created_at).toLocaleString()
                    : '—';
                const alt = drawer.querySelector('[data-drawer-alt]');
                if (alt) alt.value = item.alt_text || '';
                const folder = drawer.querySelector('[data-drawer-folder]');
                if (folder) folder.value = item.folder_uuid || '';
                drawer.hidden = false;
            });
    }

    function closeDrawer() {
        if (drawer) drawer.hidden = true;
        state.drawerUuid = null;
    }

    function enqueue(files) {
        if (!canUpload) return;
        [...files].forEach((file) => {
            state.queue.push({
                id: `${Date.now()}-${Math.random()}`,
                file,
                name: file.name,
                status: 'queued',
            });
        });
        renderQueue();
        pumpQueue();
    }

    function renderQueue() {
        if (!queueEl) return;
        const total = state.queue.length;
        if (!total) {
            queueEl.hidden = true;
            return;
        }
        queueEl.hidden = false;
        const done = state.queue.filter((item) => item.status === 'done').length;
        const failed = state.queue.filter((item) => item.status === 'error').length;
        const pct = total ? Math.round(((done + failed) / total) * 100) : 0;
        queueEl.querySelector('[data-queue-title]').textContent = `${labels.uploading} ${total} files`;
        queueEl.querySelector('[data-queue-progress]').textContent = `${done} ${labels.completed}${failed ? ` · ${failed} ${labels.failed}` : ''} · ${pct}%`;
        queueEl.querySelector('[data-queue-bar]').style.width = `${pct}%`;
        queueEl.querySelector('[data-queue-list]').innerHTML = state.queue.slice(-8).map((item) => `
            <li class="${item.status === 'error' ? 'is-error' : ''}">
                <span>${escapeHtml(item.name)}</span>
                ${item.status === 'error' ? `<button type="button" data-retry="${item.id}">${labels.retry}</button>` : `<span>${item.status}</span>`}
            </li>
        `).join('');
    }

    let reloadTimer = null;
    function scheduleReload() {
        window.clearTimeout(reloadTimer);
        reloadTimer = window.setTimeout(() => load({ reset: true }), 400);
    }

    async function uploadItem(item) {
        item.status = 'uploading';
        renderQueue();
        const body = new FormData();
        body.append('file', item.file);
        if (state.folder && !['all', 'unfiled'].includes(state.folder)) {
            body.append('folder_uuid', state.folder);
        }
        try {
            const response = await fetch(root.dataset.uploadUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body,
            });
            if (!response.ok) {
                throw new Error('upload failed');
            }
            item.status = 'done';
            scheduleReload();
        } catch {
            item.status = 'error';
        }
        renderQueue();
        pumpQueue();
    }

    function pumpQueue() {
        while (state.active < CONCURRENCY) {
            const next = state.queue.find((item) => item.status === 'queued');
            if (!next) break;
            state.active += 1;
            uploadItem(next).finally(() => {
                state.active -= 1;
            });
        }
    }

    root.querySelector('[data-open-upload]')?.addEventListener('click', () => uploadInput?.click());
    uploadInput?.addEventListener('change', () => {
        enqueue(uploadInput.files);
        uploadInput.value = '';
    });

    let searchTimer = null;
    searchInput?.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => {
            state.search = searchInput.value.trim();
            resetAndLoad();
        }, 300);
    });

    root.querySelectorAll('[data-type-filter]').forEach((chip) => {
        chip.addEventListener('click', () => {
            state.type = chip.dataset.typeFilter || '';
            resetAndLoad();
        });
    });

    root.querySelector('[data-period-filter]')?.addEventListener('change', (event) => {
        state.period = event.target.value;
        resetAndLoad();
    });

    root.addEventListener('click', (event) => {
        const folderLink = event.target.closest('[data-folder-link]');
        if (folderLink) {
            event.preventDefault();
            state.folder = folderLink.dataset.folder || 'all';
            resetAndLoad();
            return;
        }

        const retry = event.target.closest('[data-retry]');
        if (retry) {
            const item = state.queue.find((entry) => entry.id === retry.dataset.retry);
            if (item) {
                item.status = 'queued';
                pumpQueue();
            }
            return;
        }

        if (event.target.closest('[data-drawer-close]')) {
            closeDrawer();
            return;
        }

        const detailsBtn = event.target.closest('[data-open-details]');
        if (detailsBtn) {
            event.preventDefault();
            event.stopPropagation();
            const tile = detailsBtn.closest('[data-media-tile]');
            if (tile) {
                const index = tiles().indexOf(tile);
                selectTile(tile.dataset.uuid, { index });
                openDrawer(tile.dataset.uuid);
            }
            return;
        }

        const check = event.target.closest('[data-tile-check]');
        if (check) {
            event.preventDefault();
            event.stopPropagation();
            const tile = check.closest('[data-media-tile]');
            selectTile(tile.dataset.uuid, { additive: true, index: tiles().indexOf(tile) });
            return;
        }

        const tile = event.target.closest('[data-media-tile]');
        if (tile && grid.contains(tile)) {
            const index = tiles().indexOf(tile);
            selectTile(tile.dataset.uuid, {
                additive: event.metaKey || event.ctrlKey,
                range: event.shiftKey,
                index,
            });
        }
    });

    root.addEventListener('dblclick', (event) => {
        const tile = event.target.closest('[data-media-tile]');
        if (tile && grid.contains(tile)) {
            event.preventDefault();
            openDrawer(tile.dataset.uuid);
        }
    });

    drawer?.querySelector('[data-drawer-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!state.drawerUuid || !canUpdate) return;
        await fetch(`${root.dataset.showUrl}/${state.drawerUuid}`, {
            method: 'PUT',
            headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify({
                alt_text: drawer.querySelector('[data-drawer-alt]')?.value || '',
                folder_uuid: drawer.querySelector('[data-drawer-folder]')?.value || null,
            }),
        });
        closeDrawer();
        load({ reset: true });
    });

    drawer?.querySelector('[data-drawer-copy]')?.addEventListener('click', () => copyText(state.drawerUrl));
    drawer?.querySelector('[data-drawer-copy-uuid]')?.addEventListener('click', () => copyText(state.drawerUuid));
    drawer?.querySelector('[data-drawer-copy-markdown]')?.addEventListener('click', () => {
        const alt = drawer.querySelector('[data-drawer-alt]')?.value || state.drawerAlt || state.drawerFilename;
        copyText(`![${alt}](${state.drawerUrl || ''})`);
    });
    drawer?.querySelector('[data-drawer-download]')?.addEventListener('click', () => {
        if (state.drawerUuid) {
            window.location.href = `${root.dataset.downloadUrl}/${state.drawerUuid}/download`;
        }
    });
    drawer?.querySelector('[data-drawer-delete]')?.addEventListener('click', async () => {
        if (!canDelete || !state.drawerUuid || !window.confirm(labels.deleteOne)) return;
        await fetch(`${root.dataset.showUrl}/${state.drawerUuid}`, {
            method: 'DELETE',
            headers: jsonHeaders(),
        });
        closeDrawer();
        load({ reset: true });
    });

    root.querySelector('[data-bulk-clear]')?.addEventListener('click', () => {
        clearSelected();
        paintSelection();
    });

    root.querySelector('[data-select-all]')?.addEventListener('click', () => {
        state.selected = tiles().map((tile) => tile.dataset.uuid);
        paintSelection();
    });

    root.querySelector('[data-bulk-copy]')?.addEventListener('click', async () => {
        if (state.selected.length === 0) return;
        const urls = tiles()
            .filter((tile) => isSelected(tile.dataset.uuid) && tile.dataset.url)
            .map((tile) => tile.dataset.url);
        await copyText(urls.join('\n'));
    });

    root.querySelector('[data-bulk-move]')?.addEventListener('click', async () => {
        if (!canUpdate || state.selected.length === 0) return;
        await fetch(root.dataset.bulkMoveUrl, {
            method: 'POST',
            headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify({
                uuids: [...state.selected],
                folder_uuid: root.querySelector('[data-bulk-folder]')?.value || null,
            }),
        });
        clearSelected();
        load({ reset: true });
    });

    root.querySelector('[data-bulk-delete]')?.addEventListener('click', async () => {
        if (!canDelete || state.selected.length === 0 || !window.confirm(labels.deleteMany)) return;
        await fetch(root.dataset.bulkDeleteUrl, {
            method: 'POST',
            headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ uuids: [...state.selected] }),
        });
        clearSelected();
        load({ reset: true });
    });

    const importDialog = document.querySelector('[data-import-dialog]');
    root.querySelector('[data-open-import]')?.addEventListener('click', () => importDialog?.showModal());
    document.querySelector('[data-import-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const url = document.querySelector('[data-import-url]')?.value;
        if (!url) return;
        const body = { url, folder_uuid: ['all', 'unfiled'].includes(state.folder) ? null : state.folder };
        await fetch(root.dataset.importUrl, {
            method: 'POST',
            headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        importDialog?.close();
        load({ reset: true });
    });

    const folderDialog = document.querySelector('[data-folder-dialog]');
    root.querySelector('[data-open-folder]')?.addEventListener('click', () => folderDialog?.showModal());
    document.querySelector('[data-folder-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const name = document.querySelector('[data-folder-name]')?.value?.trim();
        if (!name) return;
        const parent = document.querySelector('[data-folder-parent]')?.value || null;
        const response = await fetch(root.dataset.folderStoreUrl, {
            method: 'POST',
            headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name,
                parent_uuid: parent || (['all', 'unfiled'].includes(state.folder) ? null : state.folder),
            }),
        });
        const payload = await response.json().catch(() => ({}));
        folderDialog?.close();
        if (payload.data?.uuid) {
            state.folder = payload.data.uuid;
        }
        window.location.href = `${root.dataset.browseUrl}?folder=${encodeURIComponent(state.folder)}`;
    });

    if (canUpload && stage) {
        ['dragenter', 'dragover'].forEach((type) => {
            stage.addEventListener(type, (event) => {
                event.preventDefault();
                if (overlay) overlay.hidden = false;
            });
        });
        ['dragleave', 'drop'].forEach((type) => {
            stage.addEventListener(type, (event) => {
                event.preventDefault();
                if (type === 'drop') enqueue(event.dataTransfer?.files || []);
                if (overlay) overlay.hidden = true;
            });
        });
        window.addEventListener('paste', (event) => {
            const target = event.target;
            if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement) return;
            const files = [...(event.clipboardData?.files || [])];
            if (files.length) enqueue(files);
        });
    }

    const collapsed = window.localStorage.getItem(FOLDER_STORAGE) === '1';
    if (collapsed) root.classList.add('is-folders-collapsed');
    root.querySelector('[data-toggle-folders]')?.addEventListener('click', () => {
        root.classList.toggle('is-folders-collapsed');
        window.localStorage.setItem(FOLDER_STORAGE, root.classList.contains('is-folders-collapsed') ? '1' : '0');
    });

    const storedWidth = Number(window.localStorage.getItem(WIDTH_STORAGE) || 0);
    if (storedWidth >= 200 && storedWidth <= 240) {
        root.style.setProperty('--media-folder-width', `${storedWidth}px`);
    }
    const resize = root.querySelector('[data-folder-resize]');
    resize?.addEventListener('mousedown', (event) => {
        event.preventDefault();
        const startX = event.clientX;
        const startWidth = Number.parseFloat(getComputedStyle(root).getPropertyValue('--media-folder-width')) || 232;
        const onMove = (moveEvent) => {
            const width = Math.min(240, Math.max(200, startWidth + (moveEvent.clientX - startX)));
            root.style.setProperty('--media-folder-width', `${width}px`);
            window.localStorage.setItem(WIDTH_STORAGE, String(Math.round(width)));
        };
        const onUp = () => {
            window.removeEventListener('mousemove', onMove);
            window.removeEventListener('mouseup', onUp);
        };
        window.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onUp);
    });

    function isEditableTarget(target) {
        return target instanceof HTMLInputElement
            || target instanceof HTMLTextAreaElement
            || target instanceof HTMLSelectElement
            || Boolean(target?.isContentEditable);
    }

    function columnCount() {
        return getComputedStyle(grid).gridTemplateColumns.split(' ').filter(Boolean).length || 1;
    }

    function moveFocus(delta, event) {
        const list = tiles();
        if (!list.length) return;
        const current = state.focusIndex ?? tiles().findIndex((tile) => isSelected(tile.dataset.uuid));
        const next = Math.max(0, Math.min(list.length - 1, (current < 0 ? 0 : current) + delta));
        const tile = list[next];
        tile.focus({ preventScroll: true });
        tile.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        if (event.shiftKey) {
            selectTile(tile.dataset.uuid, { range: true, index: next });
            return;
        }
        if (event.metaKey || event.ctrlKey) {
            state.focusIndex = next;
            paintSelection();
            return;
        }
        selectTile(tile.dataset.uuid, { index: next });
    }

    async function deleteSelection() {
        if (!canDelete) return;
        const uuids = state.selected.length ? [...state.selected] : [];
        if (uuids.length === 0 && state.focusIndex !== null) {
            const focused = tiles()[state.focusIndex];
            if (focused) uuids.push(focused.dataset.uuid);
        }
        if (uuids.length === 0) return;
        const confirmed = window.confirm(uuids.length > 1 ? labels.deleteMany : labels.deleteOne);
        if (!confirmed) return;
        if (uuids.length === 1 && uuids[0] === state.drawerUuid) {
            await fetch(`${root.dataset.showUrl}/${uuids[0]}`, { method: 'DELETE', headers: jsonHeaders() });
        } else {
            await fetch(root.dataset.bulkDeleteUrl, {
                method: 'POST',
                headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
                body: JSON.stringify({ uuids }),
            });
        }
        closeDrawer();
        clearSelected();
        load({ reset: true });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (drawer && !drawer.hidden) {
                closeDrawer();
                return;
            }
            if (root.contains(event.target) || state.selected.length) {
                clearSelected();
                paintSelection();
            }
            return;
        }

        if (isEditableTarget(event.target)) {
            return;
        }

        const inLibrary = root.contains(document.activeElement) || root.contains(event.target);
        if (!inLibrary && document.activeElement !== document.body) {
            return;
        }
        if (!inLibrary && document.activeElement === document.body && state.focusIndex === null && state.selected.length === 0) {
            return;
        }

        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'a' && tiles().length) {
            event.preventDefault();
            state.selected = tiles().map((tile) => tile.dataset.uuid);
            paintSelection();
            return;
        }

        if (event.key === 'Enter' && state.focusIndex !== null) {
            event.preventDefault();
            const tile = tiles()[state.focusIndex];
            if (tile) openDrawer(tile.dataset.uuid);
            return;
        }

        if (event.key === ' ' && state.focusIndex !== null && root.contains(document.activeElement)) {
            event.preventDefault();
            const tile = tiles()[state.focusIndex];
            if (tile) selectTile(tile.dataset.uuid, { additive: true, index: state.focusIndex });
            return;
        }

        if ((event.key === 'Delete' || event.key === 'Backspace') && (state.selected.length || state.focusIndex !== null)) {
            event.preventDefault();
            deleteSelection();
            return;
        }

        const cols = columnCount();
        if (event.key === 'ArrowRight') {
            event.preventDefault();
            moveFocus(1, event);
        } else if (event.key === 'ArrowLeft') {
            event.preventDefault();
            moveFocus(-1, event);
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            moveFocus(cols, event);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            moveFocus(-cols, event);
        }
    });

    const scrollRoot = document.querySelector('.admin-content');
    if (sentinel && 'IntersectionObserver' in window) {
        sentinel.hidden = state.page >= state.lastPage;
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                load({ reset: false });
            }
        }, { root: scrollRoot, rootMargin: '240px' });
        observer.observe(sentinel);
    }

    paintSelection();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-media-library]').forEach((root) => initMediaLibrary(root));
});
