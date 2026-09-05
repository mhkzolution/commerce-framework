const CONCURRENCY = 3;
const FOLDER_STORAGE = 'cf.media.foldersCollapsed';
const WIDTH_STORAGE = 'cf.media.folderWidth';
const VIEW_STORAGE = 'cf.media.view';

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
    if (size < 1048576) return `${(size / 1024).toFixed(1)} KB`;
    if (size < 1073741824) return `${(size / 1048576).toFixed(1)} MB`;
    return `${(size / 1073741824).toFixed(2)} GB`;
}

function formatDate(iso) {
    if (!iso) return '—';
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return '—';
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function jsonHeaders() {
    return {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
    };
}

function parseJsonScript(selector, fallback) {
    try {
        const node = document.querySelector(selector);
        return node ? JSON.parse(node.textContent || '{}') : fallback;
    } catch {
        return fallback;
    }
}

function splitTags(value) {
    return String(value || '')
        .split(',')
        .map((tag) => tag.trim())
        .filter(Boolean);
}

export function initMediaLibrary(root) {
    if (!root || root.dataset.ready === '1') {
        return;
    }
    root.dataset.ready = '1';

    const i18n = parseJsonScript('[data-media-labels]', {});
    const cropPresets = parseJsonScript('[data-crop-presets]', {});

    const state = {
        folder: root.dataset.folder || 'all',
        type: root.dataset.type || '',
        period: root.dataset.period || '',
        size: root.dataset.size || '',
        tag: root.dataset.tag || '',
        sort: root.dataset.sort || 'created_at',
        direction: root.dataset.direction || 'desc',
        search: root.dataset.search || '',
        view: window.localStorage.getItem(VIEW_STORAGE) === 'list' ? 'list' : 'grid',
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
        drawerItem: null,
        crop: { image: null, rect: null, preset: 'square', dragging: false },
    };

    const grid = root.querySelector('[data-media-grid]');
    const listWrap = root.querySelector('[data-media-list]');
    const listBody = root.querySelector('[data-media-list-body]');
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
    const previewEl = root.querySelector('[data-media-preview]');
    const cropDialog = document.querySelector('[data-crop-dialog]');
    const cropCanvas = cropDialog?.querySelector('[data-crop-canvas]');
    const canUpload = root.dataset.canUpload === '1';
    const canUpdate = root.dataset.canUpdate === '1';
    const canDelete = root.dataset.canDelete === '1';
    const labels = {
        selected: (count) => (i18n.selected || ':count selected').replace(':count', String(count)),
        empty: i18n.empty || 'No files yet. Upload or import to get started.',
        emptyFiltered: i18n.emptyFiltered || 'No files match these filters.',
        loadMore: i18n.loadMore || 'Loading more files…',
        end: i18n.end || 'End of library',
        uploading: i18n.uploading || 'Uploading',
        completed: i18n.completed || 'completed',
        failed: i18n.failed || 'failed',
        retry: i18n.retry || 'Retry',
        queued: i18n.queued || 'Queued',
        processing: i18n.processing || 'Generating variants…',
        deleteOne: i18n.deleteOne || 'Delete this file?',
        deleteMany: i18n.deleteMany || 'Delete selected files?',
        deleteFolder: i18n.deleteFolder || 'Delete this folder?',
        copied: i18n.copied || 'Copied',
        openDetails: i18n.openDetails || 'Open details',
        download: i18n.download || 'Download',
        delete: i18n.delete || 'Delete',
        select: i18n.select || 'Select',
        selectAll: i18n.selectAll || 'Select all :count loaded files',
        preview: i18n.preview || 'Preview',
        copyUrl: i18n.copyUrl || 'Copy URL',
        edit: i18n.edit || 'Edit',
        unfiled: i18n.unfiled || 'Unfiled',
        notUsed: i18n.notUsed || 'Not used anywhere yet.',
        inUse: i18n.inUse || 'This file is used and cannot be deleted without confirming.',
    };

    function itemNodes() {
        if (state.view === 'list') {
            return [...(listBody?.querySelectorAll('[data-media-row]') || [])];
        }
        return [...grid.querySelectorAll('[data-media-tile]')];
    }

    const tiles = itemNodes;

    function browseParams(page) {
        const params = new URLSearchParams();
        if (state.folder && state.folder !== 'all') params.set('folder', state.folder);
        if (state.type) params.set('type', state.type);
        if (state.period) params.set('period', state.period);
        if (state.size) params.set('size', state.size);
        if (state.tag) params.set('tag', state.tag);
        if (state.search) params.set('search', state.search);
        if (state.sort) params.set('sort', state.sort);
        if (state.direction) params.set('direction', state.direction);
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
        root.querySelectorAll('[data-sort]').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.sort === state.sort);
            button.dataset.direction = button.dataset.sort === state.sort ? state.direction : '';
        });
    }

    function setView(view) {
        state.view = view === 'list' ? 'list' : 'grid';
        root.classList.toggle('is-list-view', state.view === 'list');
        if (grid) grid.hidden = state.view === 'list';
        if (listWrap) listWrap.hidden = state.view !== 'list';
        root.querySelectorAll('[data-view-toggle] [data-view]').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.view === state.view);
        });
        window.localStorage.setItem(VIEW_STORAGE, state.view);
        paintSelection();
    }

    function tileActions(item) {
        const deleteBtn = canDelete
            ? `<button type="button" data-tile-delete aria-label="${labels.delete}">${labels.delete}</button>`
            : '';
        return `
            <div class="cf-media-tile__actions">
                <button type="button" data-tile-preview aria-label="${labels.preview}">${labels.preview}</button>
                <button type="button" data-tile-copy aria-label="${labels.copyUrl}">${labels.copyUrl}</button>
                <button type="button" data-tile-edit aria-label="${labels.edit}">${labels.edit}</button>
                <button type="button" data-tile-download aria-label="${labels.download}">${labels.download}</button>
                ${deleteBtn}
            </div>
        `;
    }

    function mediaAttrs(item) {
        return `data-uuid="${escapeHtml(item.uuid)}" data-url="${escapeHtml(item.url || '')}" data-alt="${escapeHtml(item.alt_text || item.original_filename || '')}" data-filename="${escapeHtml(item.original_filename || '')}" data-mime="${escapeHtml(item.mime_type || '')}" data-type="${escapeHtml(item.media_type || '')}" data-size="${escapeHtml(item.size || 0)}" data-width="${escapeHtml(item.width || '')}" data-height="${escapeHtml(item.height || '')}" data-folder="${escapeHtml(item.folder_name || '')}" data-created="${escapeHtml(item.created_at || '')}"`;
    }

    function tileHtml(item) {
        const isImage = (item.mime_type || '').startsWith('image/');
        const thumb = item.preview_url || item.url;
        const body = isImage && thumb
            ? `<img src="${escapeHtml(thumb)}" alt="${escapeHtml(item.alt_text || item.original_filename)}" loading="lazy" decoding="async">`
            : `<div class="cf-media-tile__file"><span>${escapeHtml((item.media_type || 'file').toUpperCase())}</span></div>`;
        const dims = item.width && item.height ? `${item.width}×${item.height} · ` : '';
        return `
            <article class="cf-media-tile" data-media-tile ${mediaAttrs(item)} tabindex="0">
                <div class="cf-media-tile__thumb">
                    ${body}
                    <div class="cf-media-tile__overlay" aria-hidden="true"></div>
                    <button type="button" class="cf-media-tile__badge" data-tile-check aria-label="${labels.select}"></button>
                    ${tileActions(item)}
                </div>
                <p class="cf-media-tile__name" title="${escapeHtml(item.original_filename)}">${escapeHtml(item.original_filename)}</p>
                <p class="cf-media-tile__meta">${dims}${formatBytes(item.size)} · ${formatDate(item.created_at)}</p>
            </article>
        `;
    }

    function listRowHtml(item) {
        const isImage = (item.mime_type || '').startsWith('image/');
        const thumb = item.preview_url || item.url;
        const preview = isImage && thumb
            ? `<img src="${escapeHtml(thumb)}" alt="${escapeHtml(item.alt_text || item.original_filename)}" loading="lazy" decoding="async">`
            : `<span class="cf-media-row__file">${escapeHtml((item.media_type || 'file').toUpperCase())}</span>`;
        const dims = item.width && item.height ? `${item.width}×${item.height}` : '—';
        return `
            <tr class="cf-media-row" data-media-row ${mediaAttrs(item)} tabindex="0">
                <td class="cf-media-row__check">
                    <button type="button" class="cf-media-tile__badge" data-tile-check aria-label="${labels.select}"></button>
                </td>
                <td class="cf-media-row__preview">${preview}</td>
                <td class="cf-media-row__name" title="${escapeHtml(item.original_filename)}">${escapeHtml(item.original_filename || '')}</td>
                <td>${escapeHtml(item.mime_type || '')}</td>
                <td>${dims}</td>
                <td>${formatBytes(item.size)}</td>
                <td>${escapeHtml(item.folder_name || labels.unfiled)}</td>
                <td>${formatDate(item.created_at)}</td>
            </tr>
        `;
    }

    function isFiltered() {
        return Boolean(state.search || state.type || state.period || state.size || state.tag);
    }

    function renderLibrary(items, { append = false } = {}) {
        if (!append) {
            if (!items.length) {
                grid.innerHTML = `<p class="cf-media-empty" data-empty-state>${escapeHtml(isFiltered() ? labels.emptyFiltered : labels.empty)}</p>`;
                if (listBody) listBody.innerHTML = '';
                return;
            }
            grid.innerHTML = items.map(tileHtml).join('');
            if (listBody) listBody.innerHTML = items.map(listRowHtml).join('');
            return;
        }
        grid.querySelector('[data-empty-state]')?.remove();
        grid.insertAdjacentHTML('beforeend', items.map(tileHtml).join(''));
        listBody?.insertAdjacentHTML('beforeend', items.map(listRowHtml).join(''));
    }

    function paintInsights(insights) {
        if (!insights) return;
        const set = (key, value) => {
            const el = root.querySelector(`[data-insight="${key}"]`);
            if (el) el.textContent = value;
        };
        set('total', String(insights.total ?? 0));
        set('storage', formatBytes(insights.storage_bytes ?? 0));
        set('images', String(insights.images ?? 0));
        set('videos', String(insights.videos ?? 0));
        set('documents', String(insights.documents ?? 0));
        const recent = root.querySelector('[data-insight-recent]');
        if (!recent || !Array.isArray(insights.recent)) return;
        recent.innerHTML = insights.recent.slice(0, 8).map((item) => {
            const thumb = item.preview_url || item.url;
            const isImage = (item.mime_type || '').startsWith('image/');
            const body = isImage && thumb
                ? `<img src="${escapeHtml(thumb)}" alt="${escapeHtml(item.original_filename || '')}">`
                : `<span>${escapeHtml((item.media_type || 'file').toUpperCase())}</span>`;
            return `<button type="button" data-recent-uuid="${escapeHtml(item.uuid)}" title="${escapeHtml(item.original_filename || '')}">${body}</button>`;
        }).join('') || `<em>${escapeHtml(labels.empty)}</em>`;
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

    function paintNode(node, focused) {
        const order = state.selected.indexOf(node.dataset.uuid);
        const on = order >= 0;
        node.classList.toggle('is-selected', on);
        node.classList.toggle('is-focused', Boolean(focused));
        const badge = node.querySelector('[data-tile-check]');
        if (badge) badge.textContent = on ? String(order + 1) : '';
    }

    function paintSelection() {
        const visible = itemNodes();
        visible.forEach((node, index) => {
            node.dataset.index = String(index);
            paintNode(node, state.focusIndex === index);
        });
        const other = state.view === 'list'
            ? [...grid.querySelectorAll('[data-media-tile]')]
            : [...(listBody?.querySelectorAll('[data-media-row]') || [])];
        other.forEach((node) => paintNode(node, false));
        const count = state.selected.length;
        if (bulkBar) {
            bulkBar.classList.toggle('is-idle', count === 0);
            if (bulkCount) bulkCount.textContent = labels.selected(count);
            bulkBar.querySelectorAll('[data-bulk-action]').forEach((control) => {
                control.disabled = count === 0;
            });
            const selectAll = bulkBar.querySelector('[data-select-all]');
            if (selectAll) {
                const loaded = visible.length;
                const template = selectAll.dataset.selectAllTemplate || labels.selectAll;
                selectAll.textContent = template.replace(':count', String(loaded));
                selectAll.disabled = loaded === 0 || count === loaded;
            }
        }
    }

    function selectTile(uuid, { additive = false, range = false, index = null } = {}) {
        if (range && state.lastIndex !== null && index !== null) {
            const list = itemNodes();
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
            itemNodes()[index]?.focus({ preventScroll: true });
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
            paintInsights(meta.insights);
            renderLibrary(items, { append: !reset });
            if (reset) clearSelected();
            if (status) {
                status.textContent = state.page >= state.lastPage && (reset ? items.length : true)
                    ? (itemNodes().length ? labels.end : '')
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

    function openPreview(url, alt) {
        if (!previewEl || !url) return;
        const image = previewEl.querySelector('[data-preview-image]');
        if (image) {
            image.src = url;
            image.alt = alt || '';
        }
        previewEl.hidden = false;
    }

    function closePreview() {
        if (previewEl) previewEl.hidden = true;
    }

    function fillDrawer(item) {
        state.drawerUrl = item.url;
        state.drawerAlt = item.alt_text || item.original_filename || '';
        state.drawerFilename = item.original_filename || '';
        state.drawerItem = item;
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
        const caption = drawer.querySelector('[data-drawer-caption]');
        if (caption) caption.value = item.caption || '';
        const description = drawer.querySelector('[data-drawer-description]');
        if (description) description.value = item.description || '';
        const tags = drawer.querySelector('[data-drawer-tags]');
        if (tags) tags.value = (item.tags || []).map((tag) => tag.name).join(', ');
        const folder = drawer.querySelector('[data-drawer-folder]');
        if (folder) folder.value = item.folder_uuid || '';
        const variants = drawer.querySelector('[data-drawer-variants]');
        if (variants) {
            const rows = [
                { name: 'Original', url: item.url },
                ...(item.variants || []),
            ].filter((row) => row.url);
            variants.innerHTML = rows.map((row) => `
                <li>
                    <span>${escapeHtml(row.name)}</span>
                    <button type="button" data-copy-url="${escapeHtml(row.url)}">${labels.copyUrl}</button>
                </li>
            `).join('');
        }
        const usage = drawer.querySelector('[data-drawer-usage]');
        if (usage) {
            const rows = item.usage || [];
            usage.innerHTML = rows.length
                ? rows.map((row) => `<li><strong>${escapeHtml(row.label || '')}</strong> ${escapeHtml(row.title || '')}</li>`).join('')
                : `<li>${escapeHtml(labels.notUsed)}</li>`;
        }
        drawer.hidden = false;
    }

    function openDrawer(uuid) {
        state.drawerUuid = uuid;
        fetch(`${root.dataset.showUrl}/${uuid}`, { headers: jsonHeaders() })
            .then((res) => res.json())
            .then((payload) => {
                if (payload.data) fillDrawer(payload.data);
            });
    }

    function closeDrawer() {
        if (drawer) drawer.hidden = true;
        state.drawerUuid = null;
        state.drawerItem = null;
    }

    function enqueue(files) {
        if (!canUpload) return;
        [...files].forEach((file) => {
            state.queue.push({
                id: `${Date.now()}-${Math.random()}`,
                file,
                name: file.name,
                status: 'queued',
                progress: 0,
            });
        });
        renderQueue();
        pumpQueue();
    }

    function statusLabel(item) {
        if (item.status === 'uploading') return `${labels.uploading}… ${item.progress || 0}%`;
        if (item.status === 'processing') return labels.processing;
        if (item.status === 'done') return labels.completed;
        if (item.status === 'error') return labels.failed;
        return labels.queued;
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
                ${item.status === 'error' ? `<button type="button" data-retry="${item.id}">${labels.retry}</button>` : `<span>${statusLabel(item)}</span>`}
            </li>
        `).join('');
    }

    let reloadTimer = null;
    function scheduleReload() {
        window.clearTimeout(reloadTimer);
        reloadTimer = window.setTimeout(() => load({ reset: true }), 400);
    }

    function uploadItem(item) {
        item.status = 'uploading';
        item.progress = 0;
        renderQueue();
        const body = new FormData();
        body.append('file', item.file);
        if (state.folder && !['all', 'unfiled'].includes(state.folder)) {
            body.append('folder_uuid', state.folder);
        }

        return new Promise((resolve) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', root.dataset.uploadUrl);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.upload.addEventListener('progress', (event) => {
                if (!event.lengthComputable) return;
                item.progress = Math.round((event.loaded / event.total) * 100);
                if (item.progress >= 100) item.status = 'processing';
                renderQueue();
            });
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    item.status = 'done';
                    scheduleReload();
                } else {
                    item.status = 'error';
                }
                renderQueue();
                resolve();
            });
            xhr.addEventListener('error', () => {
                item.status = 'error';
                renderQueue();
                resolve();
            });
            xhr.send(body);
        });
    }

    function pumpQueue() {
        while (state.active < CONCURRENCY) {
            const next = state.queue.find((item) => item.status === 'queued');
            if (!next) break;
            state.active += 1;
            uploadItem(next).finally(() => {
                state.active -= 1;
                pumpQueue();
            });
        }
    }

    async function deleteUuids(uuids, { force = false } = {}) {
        if (!canDelete || uuids.length === 0) return false;
        const single = uuids.length === 1;
        const url = single
            ? `${root.dataset.showUrl}/${uuids[0]}`
            : root.dataset.bulkDeleteUrl;
        const response = await fetch(url, {
            method: single ? 'DELETE' : 'POST',
            headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify(single ? { force } : { uuids, force }),
        });
        if (response.status === 409) {
            const payload = await response.json().catch(() => ({}));
            const confirmed = window.confirm(`${payload.message || labels.inUse}\n\n${labels.deleteMany}`);
            if (confirmed) {
                return deleteUuids(uuids, { force: true });
            }
            return false;
        }
        return response.ok;
    }

    function mediaFromNode(node) {
        return {
            uuid: node.dataset.uuid,
            url: node.dataset.url,
            alt_text: node.dataset.alt,
            original_filename: node.dataset.filename,
        };
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
        }, 220);
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

    root.querySelector('[data-size-filter]')?.addEventListener('change', (event) => {
        state.size = event.target.value;
        resetAndLoad();
    });

    root.querySelector('[data-tag-filter]')?.addEventListener('change', (event) => {
        state.tag = event.target.value;
        resetAndLoad();
    });

    root.querySelectorAll('[data-view-toggle] [data-view]').forEach((button) => {
        button.addEventListener('click', () => setView(button.dataset.view));
    });

    root.querySelectorAll('[data-sort]').forEach((button) => {
        button.addEventListener('click', () => {
            const next = button.dataset.sort;
            if (state.sort === next) {
                state.direction = state.direction === 'asc' ? 'desc' : 'asc';
            } else {
                state.sort = next;
                state.direction = 'asc';
            }
            resetAndLoad();
        });
    });

    root.addEventListener('click', (event) => {
        const previewClose = event.target.closest('[data-preview-close]');
        if (previewClose) {
            closePreview();
            return;
        }

        const recent = event.target.closest('[data-recent-uuid]');
        if (recent) {
            openDrawer(recent.dataset.recentUuid);
            return;
        }

        const renameFolder = event.target.closest('[data-rename-folder]');
        if (renameFolder) {
            event.preventDefault();
            event.stopPropagation();
            openFolderDialog({
                uuid: renameFolder.dataset.folder,
                name: renameFolder.dataset.folderName,
                parentUuid: renameFolder.dataset.parentUuid || '',
            });
            return;
        }

        const deleteFolder = event.target.closest('[data-delete-folder]');
        if (deleteFolder) {
            event.preventDefault();
            event.stopPropagation();
            if (!window.confirm(labels.deleteFolder)) return;
            fetch(`${root.dataset.folderBaseUrl}/${deleteFolder.dataset.folder}`, {
                method: 'DELETE',
                headers: jsonHeaders(),
            }).then(() => {
                window.location.href = root.dataset.browseUrl;
            });
            return;
        }

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

        const copyUrl = event.target.closest('[data-copy-url]');
        if (copyUrl) {
            copyText(copyUrl.dataset.copyUrl);
            return;
        }

        const actionNode = event.target.closest('[data-tile-preview], [data-tile-copy], [data-tile-edit], [data-tile-download], [data-tile-delete]');
        if (actionNode) {
            event.preventDefault();
            event.stopPropagation();
            const node = actionNode.closest('[data-media-tile], [data-media-row]');
            if (!node) return;
            const item = mediaFromNode(node);
            if (actionNode.hasAttribute('data-tile-preview')) {
                openPreview(item.url, item.alt_text);
                return;
            }
            if (actionNode.hasAttribute('data-tile-copy')) {
                copyText(item.url);
                return;
            }
            if (actionNode.hasAttribute('data-tile-edit')) {
                openDrawer(item.uuid);
                return;
            }
            if (actionNode.hasAttribute('data-tile-download')) {
                window.location.href = `${root.dataset.downloadUrl}/${item.uuid}/download`;
                return;
            }
            if (actionNode.hasAttribute('data-tile-delete')) {
                if (!window.confirm(labels.deleteOne)) return;
                deleteUuids([item.uuid]).then((ok) => {
                    if (ok) {
                        closeDrawer();
                        load({ reset: true });
                    }
                });
            }
            return;
        }

        const detailsBtn = event.target.closest('[data-open-details]');
        if (detailsBtn) {
            event.preventDefault();
            event.stopPropagation();
            const tile = detailsBtn.closest('[data-media-tile], [data-media-row]');
            if (tile) {
                const index = itemNodes().indexOf(tile);
                selectTile(tile.dataset.uuid, { index: index >= 0 ? index : null });
                openDrawer(tile.dataset.uuid);
            }
            return;
        }

        const check = event.target.closest('[data-tile-check]');
        if (check) {
            event.preventDefault();
            event.stopPropagation();
            const tile = check.closest('[data-media-tile], [data-media-row]');
            const index = itemNodes().indexOf(tile);
            selectTile(tile.dataset.uuid, { additive: true, index: index >= 0 ? index : null });
            return;
        }

        const tile = event.target.closest('[data-media-tile], [data-media-row]');
        if (tile && (grid.contains(tile) || listWrap?.contains(tile))) {
            const visible = itemNodes();
            const index = visible.indexOf(tile);
            selectTile(tile.dataset.uuid, {
                additive: event.metaKey || event.ctrlKey,
                range: event.shiftKey,
                index: index >= 0 ? index : null,
            });
        }
    });

    root.addEventListener('dblclick', (event) => {
        const tile = event.target.closest('[data-media-tile], [data-media-row]');
        if (tile && (grid.contains(tile) || listWrap?.contains(tile))) {
            event.preventDefault();
            openDrawer(tile.dataset.uuid);
        }
    });

    previewEl?.addEventListener('click', (event) => {
        if (event.target === previewEl || event.target.closest('[data-preview-close]')) {
            closePreview();
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
                caption: drawer.querySelector('[data-drawer-caption]')?.value || '',
                description: drawer.querySelector('[data-drawer-description]')?.value || '',
                tags: splitTags(drawer.querySelector('[data-drawer-tags]')?.value),
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
        const ok = await deleteUuids([state.drawerUuid]);
        if (ok) {
            closeDrawer();
            load({ reset: true });
        }
    });

    const replaceInput = drawer?.querySelector('[data-replace-input]');
    drawer?.querySelector('[data-drawer-replace]')?.addEventListener('click', () => replaceInput?.click());
    replaceInput?.addEventListener('change', async () => {
        if (!state.drawerUuid || !replaceInput.files?.[0]) return;
        const body = new FormData();
        body.append('file', replaceInput.files[0]);
        const response = await fetch(`${root.dataset.showUrl}/${state.drawerUuid}/replace`, {
            method: 'POST',
            headers: jsonHeaders(),
            body,
        });
        replaceInput.value = '';
        if (response.ok) {
            const payload = await response.json();
            if (payload.data) fillDrawer(payload.data);
            load({ reset: true });
        }
    });

    function cropRectForPreset(image, preset) {
        const ratio = Number(cropPresets[preset]?.ratio) || 1;
        const width = image.naturalWidth;
        const height = image.naturalHeight;
        let cropW = width;
        let cropH = cropW / ratio;
        if (cropH > height) {
            cropH = height;
            cropW = cropH * ratio;
        }
        return {
            x: Math.round((width - cropW) / 2),
            y: Math.round((height - cropH) / 2),
            width: Math.round(cropW),
            height: Math.round(cropH),
            preset,
        };
    }

    function drawCrop() {
        if (!cropCanvas || !state.crop.image) return;
        const ctx = cropCanvas.getContext('2d');
        const image = state.crop.image;
        const maxW = 480;
        const scale = Math.min(maxW / image.naturalWidth, 320 / image.naturalHeight, 1);
        cropCanvas.width = Math.round(image.naturalWidth * scale);
        cropCanvas.height = Math.round(image.naturalHeight * scale);
        ctx.clearRect(0, 0, cropCanvas.width, cropCanvas.height);
        ctx.drawImage(image, 0, 0, cropCanvas.width, cropCanvas.height);
        const rect = state.crop.rect;
        if (!rect) return;
        const x = rect.x * scale;
        const y = rect.y * scale;
        const w = rect.width * scale;
        const h = rect.height * scale;
        ctx.fillStyle = 'rgba(15, 23, 42, 0.45)';
        ctx.fillRect(0, 0, cropCanvas.width, cropCanvas.height);
        ctx.clearRect(x, y, w, h);
        ctx.drawImage(image, rect.x, rect.y, rect.width, rect.height, x, y, w, h);
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 2;
        ctx.strokeRect(x, y, w, h);
    }

    drawer?.querySelector('[data-drawer-crop]')?.addEventListener('click', () => {
        if (!state.drawerUrl || !cropDialog) return;
        const image = new Image();
        image.onload = () => {
            state.crop.image = image;
            state.crop.rect = cropRectForPreset(image, state.crop.preset || 'square');
            drawCrop();
            cropDialog.showModal();
        };
        image.src = state.drawerUrl;
    });

    cropDialog?.querySelectorAll('[data-crop-preset]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            if (!state.crop.image) return;
            state.crop.preset = button.dataset.cropPreset;
            state.crop.rect = cropRectForPreset(state.crop.image, state.crop.preset);
            drawCrop();
        });
    });

    cropDialog?.querySelector('[data-crop-form]')?.addEventListener('submit', async (event) => {
        if (event.submitter?.hasAttribute('data-apply-crop') || event.submitter?.value === 'default') {
            event.preventDefault();
            if (!state.drawerUuid || !state.crop.rect) {
                cropDialog.close();
                return;
            }
            await fetch(`${root.dataset.showUrl}/${state.drawerUuid}`, {
                method: 'PUT',
                headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
                body: JSON.stringify({ crop: state.crop.rect }),
            });
            cropDialog.close();
            openDrawer(state.drawerUuid);
        }
    });

    root.querySelector('[data-bulk-clear]')?.addEventListener('click', () => {
        clearSelected();
        paintSelection();
    });

    root.querySelector('[data-select-all]')?.addEventListener('click', () => {
        state.selected = itemNodes().map((tile) => tile.dataset.uuid);
        paintSelection();
    });

    root.querySelector('[data-bulk-copy]')?.addEventListener('click', async () => {
        if (state.selected.length === 0) return;
        const urls = [...grid.querySelectorAll('[data-media-tile]')]
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

    root.querySelector('[data-bulk-tag]')?.addEventListener('click', async () => {
        if (!canUpdate || state.selected.length === 0) return;
        const tags = splitTags(root.querySelector('[data-bulk-tags]')?.value);
        if (!tags.length) return;
        await fetch(root.dataset.bulkTagUrl, {
            method: 'POST',
            headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ uuids: [...state.selected], tags }),
        });
        clearSelected();
        load({ reset: true });
    });

    root.querySelector('[data-bulk-regenerate]')?.addEventListener('click', async () => {
        if (!canUpdate || state.selected.length === 0) return;
        await fetch(root.dataset.bulkRegenerateUrl, {
            method: 'POST',
            headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ uuids: [...state.selected] }),
        });
        clearSelected();
        load({ reset: true });
    });

    root.querySelector('[data-bulk-delete]')?.addEventListener('click', async () => {
        if (!canDelete || state.selected.length === 0 || !window.confirm(labels.deleteMany)) return;
        const ok = await deleteUuids([...state.selected]);
        if (ok) {
            clearSelected();
            load({ reset: true });
        }
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
    const folderNameInput = document.querySelector('[data-folder-name]');
    const folderParent = document.querySelector('[data-folder-parent]');
    const folderEditUuid = document.querySelector('[data-folder-edit-uuid]');
    const folderTitle = document.querySelector('[data-folder-dialog-title]');
    const folderSubmit = document.querySelector('[data-folder-submit]');
    const folderParentWrap = document.querySelector('[data-folder-parent-wrap]');

    function openFolderDialog(edit = null) {
        if (!folderDialog) return;
        if (folderEditUuid) {
            folderEditUuid.value = edit?.uuid || '';
            folderEditUuid.dataset.parentUuid = edit?.parentUuid || '';
        }
        if (folderNameInput) folderNameInput.value = edit?.name || '';
        if (folderTitle) folderTitle.textContent = edit ? (i18n.rename_folder || 'Rename') : (i18n.create_folder || 'Create folder');
        if (folderSubmit) folderSubmit.textContent = edit ? (i18n.rename_folder || 'Rename') : (i18n.create_folder || 'Create folder');
        if (folderParentWrap) folderParentWrap.hidden = Boolean(edit);
        folderDialog.showModal();
    }

    root.querySelector('[data-open-folder]')?.addEventListener('click', () => openFolderDialog());
    document.querySelector('[data-folder-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const name = folderNameInput?.value?.trim();
        if (!name) return;
        const editing = folderEditUuid?.value;
        if (editing) {
            await fetch(`${root.dataset.folderBaseUrl}/${editing}`, {
                method: 'PUT',
                headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name,
                    parent_uuid: folderEditUuid.dataset.parentUuid || null,
                }),
            });
            folderDialog?.close();
            window.location.reload();
            return;
        }
        const parent = folderParent?.value || null;
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
        if (state.view === 'list') return 1;
        return getComputedStyle(grid).gridTemplateColumns.split(' ').filter(Boolean).length || 1;
    }

    function moveFocus(delta, event) {
        const list = itemNodes();
        if (!list.length) return;
        const current = state.focusIndex ?? list.findIndex((tile) => isSelected(tile.dataset.uuid));
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
            const focused = itemNodes()[state.focusIndex];
            if (focused) uuids.push(focused.dataset.uuid);
        }
        if (uuids.length === 0) return;
        const confirmed = window.confirm(uuids.length > 1 ? labels.deleteMany : labels.deleteOne);
        if (!confirmed) return;
        const ok = await deleteUuids(uuids);
        if (ok) {
            closeDrawer();
            clearSelected();
            load({ reset: true });
        }
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (previewEl && !previewEl.hidden) {
                closePreview();
                return;
            }
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

        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'a' && itemNodes().length) {
            event.preventDefault();
            state.selected = itemNodes().map((tile) => tile.dataset.uuid);
            paintSelection();
            return;
        }

        if (event.key === 'Enter' && state.focusIndex !== null) {
            event.preventDefault();
            const tile = itemNodes()[state.focusIndex];
            if (tile) openDrawer(tile.dataset.uuid);
            return;
        }

        if (event.key === ' ' && state.focusIndex !== null && root.contains(document.activeElement)) {
            event.preventDefault();
            const tile = itemNodes()[state.focusIndex];
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

    setView(state.view);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-media-library]').forEach((root) => initMediaLibrary(root));
});
