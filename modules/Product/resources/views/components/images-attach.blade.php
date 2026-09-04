@props([
    'mediaUuids' => [],
    'mediaPreviews' => [],
    'mediaTypes' => [],
    'label' => 'Product images',
    'showLabel' => true,
    'help' => 'Upload a new image, import from URL, or pick from the media library. The first image is the primary image.',
    'imagesOnly' => true,
])

@php
    $attachId = 'images-attach-' . uniqid();
    $acceptAttr = $imagesOnly
        ? '.jpg,.jpeg,.png,.gif,.webp,.svg'
        : '.jpg,.jpeg,.png,.gif,.webp,.svg,.mp4,.webm,.mov,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip';
    $uploadLabel = $imagesOnly ? 'image' : 'file';
@endphp

<div
    id="{{ $attachId }}"
    class="cf-file-attach"
    data-images-attach
    data-upload-url="{{ route('admin.media.store') }}"
    data-import-url="{{ route('admin.media.import') }}"
    data-picker-url="{{ route('admin.media.picker') }}"
    data-images-only="{{ $imagesOnly ? '1' : '0' }}"
>
    @if ($showLabel)
        <label class="block text-sm font-medium text-text">{{ $label }}</label>
    @endif

    @if ($help)
        <p class="mt-1 text-xs text-muted">{{ $help }}</p>
    @endif

    <div class="mt-3 flex flex-wrap gap-3" data-images-list>
        @foreach ($mediaUuids as $uuid)
            <div class="relative" data-image-item="{{ $uuid }}" data-media-type="{{ $mediaTypes[$uuid] ?? 'image' }}" draggable="true">
                <input type="hidden" name="media_uuids[]" value="{{ $uuid }}">
                <div class="cf-file-attach__preview flex h-20 w-20 items-center justify-center overflow-hidden rounded-lg border border-border bg-surface-muted cursor-grab">
                    @php($mediaType = $mediaTypes[$uuid] ?? 'image')
                    @if (! empty($mediaPreviews[$uuid]) && $mediaType === 'image')
                        <img src="{{ $mediaPreviews[$uuid] }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="px-1 text-center text-[10px] uppercase text-muted">{{ $mediaType }}</span>
                    @endif
                </div>
                <button type="button" class="absolute -right-2 -top-2 rounded-full bg-card px-1.5 text-xs text-muted shadow hover:text-text" data-image-remove>&times;</button>
            </div>
        @endforeach
    </div>

    <div class="mt-4 space-y-3 border-t border-border pt-4">
        <div
            class="cf-file-attach__preview mx-auto flex h-24 w-24 items-center justify-center overflow-hidden rounded-lg border border-border bg-surface-muted"
            data-attach-preview
        >
            <span class="px-2 text-center text-xs text-muted">Add {{ $uploadLabel }}</span>
        </div>

        <div class="space-y-3">
            <div class="flex flex-wrap gap-2" role="tablist">
                <button type="button" class="cf-file-attach__tab is-active" data-attach-tab="upload">Upload</button>
                <button type="button" class="cf-file-attach__tab" data-attach-tab="url">From URL</button>
                <button type="button" class="cf-file-attach__tab" data-attach-tab="library">Library</button>
            </div>

            <div data-attach-panel="upload">
                <div class="cf-file-attach__dropzone" data-attach-dropzone>
                    <input type="file" class="cf-file-attach__file-input" data-attach-file accept="{{ $acceptAttr }}">
                    <p class="text-sm font-medium text-text">Drag & drop a {{ $uploadLabel }} here</p>
                    <p class="mt-1 text-xs text-muted">or click to browse</p>
                </div>
            </div>

            <div class="hidden" data-attach-panel="url">
                <div class="flex flex-col gap-2">
                    <input type="url" class="cf-input" placeholder="https://example.com/image.png" data-attach-url>
                    <button type="button" class="cf-btn cf-btn-secondary" data-attach-import>Import</button>
                </div>
            </div>

            <div class="hidden space-y-3" data-attach-panel="library">
                <input type="search" placeholder="Search library..." class="cf-input" data-attach-library-search>
                <div class="grid max-h-56 grid-cols-3 gap-2 overflow-y-auto" data-attach-library-grid></div>
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-muted">
                    <p data-attach-library-meta>Loading library...</p>
                    <div class="flex gap-2" data-attach-library-pagination hidden>
                        <button type="button" class="cf-btn cf-btn-secondary px-2 py-1 text-xs" data-attach-library-prev>Previous</button>
                        <button type="button" class="cf-btn cf-btn-secondary px-2 py-1 text-xs" data-attach-library-next>Next</button>
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <p class="text-sm text-danger hidden" data-attach-error></p>
                <p class="text-sm text-muted hidden" data-attach-status></p>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-images-attach]').forEach((root) => {
                    const list = root.querySelector('[data-images-list]');
                    const preview = root.querySelector('[data-attach-preview]');
                    const fileInput = root.querySelector('[data-attach-file]');
                    const dropzone = root.querySelector('[data-attach-dropzone]');
                    const urlInput = root.querySelector('[data-attach-url]');
                    const errorEl = root.querySelector('[data-attach-error]');
                    const statusEl = root.querySelector('[data-attach-status]');
                    const libraryGrid = root.querySelector('[data-attach-library-grid]');
                    const libraryMeta = root.querySelector('[data-attach-library-meta]');
                    const librarySearch = root.querySelector('[data-attach-library-search]');
                    const libraryPagination = root.querySelector('[data-attach-library-pagination]');
                    const libraryPrev = root.querySelector('[data-attach-library-prev]');
                    const libraryNext = root.querySelector('[data-attach-library-next]');
                    const uploadUrl = root.dataset.uploadUrl;
                    const importUrl = root.dataset.importUrl;
                    const pickerUrl = root.dataset.pickerUrl;
                    const imagesOnly = root.dataset.imagesOnly !== '0';
                    const uploadLabel = imagesOnly ? 'image' : 'file';
                    const csrf = @json(csrf_token());
                    let page = 1;
                    let libraryLoaded = false;
                    let searchTimer = null;

                    const existing = () => new Set(
                        [...root.querySelectorAll('[data-image-item]')].map((el) => el.dataset.imageItem),
                    );

                    const setError = (message) => {
                        errorEl.textContent = message || '';
                        errorEl.classList.toggle('hidden', !message);
                    };

                    const setStatus = (message) => {
                        statusEl.textContent = message || '';
                        statusEl.classList.toggle('hidden', !message);
                    };

                    const resetPreview = () => {
                        preview.innerHTML = `<span class="px-2 text-center text-xs text-muted">Add ${uploadLabel}</span>`;
                    };

                    const renderPreview = (item) => {
                        const type = item.media_type || 'image';
                        const url = item.preview_url || item.url;

                        if (type === 'image' && url) {
                            return `<img src="${url}" alt="" class="h-full w-full object-cover">`;
                        }

                        return `<span class="px-1 text-center text-[10px] uppercase text-muted">${type}</span>`;
                    };

                    const appendImage = (item) => {
                        if (existing().has(item.uuid)) {
                            setError(`This ${uploadLabel} is already attached.`);
                            return;
                        }

                        const wrapper = document.createElement('div');
                        wrapper.className = 'relative';
                        wrapper.dataset.imageItem = item.uuid;
                        wrapper.dataset.mediaType = item.media_type || 'image';
                        wrapper.draggable = true;
                        wrapper.innerHTML = `
                            <input type="hidden" name="media_uuids[]" value="${item.uuid}">
                            <div class="cf-file-attach__preview flex h-20 w-20 items-center justify-center overflow-hidden rounded-lg border border-border bg-surface-muted cursor-grab">
                                ${renderPreview(item)}
                            </div>
                            <button type="button" class="absolute -right-2 -top-2 rounded-full bg-card px-1.5 text-xs text-muted shadow hover:text-text" data-image-remove>&times;</button>
                        `;
                        list.appendChild(wrapper);
                        resetPreview();
                        setError('');
                        setStatus(`${uploadLabel.charAt(0).toUpperCase()}${uploadLabel.slice(1)} attached.`);
                        libraryLoaded = false;
                    };

                    const renderLibraryItems = (items, pagination, emptyMessage) => {
                        libraryGrid.innerHTML = '';
                        const taken = existing();

                        if (!items.length) {
                            libraryGrid.innerHTML = `<p class="col-span-full py-8 text-center text-sm text-muted">${emptyMessage}</p>`;
                        } else {
                            items.forEach((item) => {
                                if (taken.has(item.uuid)) {
                                    return;
                                }

                                const button = document.createElement('button');
                                button.type = 'button';
                                button.className = 'overflow-hidden rounded-md border border-border text-left hover:border-primary focus:border-primary focus:outline-none';
                                button.innerHTML = `
                                    <div class="flex aspect-square items-center justify-center bg-surface-muted">
                                        ${(item.mime_type || '').startsWith('image/') && (item.url || item.preview_url)
                                            ? `<img src="${item.url || item.preview_url}" alt="${item.filename || 'Media'}" class="h-full w-full object-cover" loading="lazy">`
                                            : `<span class="px-2 text-xs text-muted">${item.filename || 'File'}</span>`}
                                    </div>
                                `;
                                button.addEventListener('click', () => appendImage(item));
                                libraryGrid.appendChild(button);
                            });
                        }

                        if (pagination) {
                            libraryMeta.textContent = `Page ${pagination.current_page} of ${pagination.last_page} · ${pagination.total} items`;
                        }
                    };

                    const uploadFile = async (file) => {
                        setError('');
                        setStatus('Uploading...');

                        const formData = new FormData();
                        formData.append('file', file);

                        const response = await fetch(uploadUrl, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: formData,
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            setStatus('');
                            setError(payload.message || 'Upload failed.');
                            return;
                        }

                        appendImage(payload.data);
                    };

                    const importUrlFile = async () => {
                        setError('');
                        setStatus('Importing...');

                        const response = await fetch(importUrl, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({ url: urlInput.value }),
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            setStatus('');
                            setError(payload.message || 'Import failed.');
                            return;
                        }

                        appendImage(payload.data);
                        urlInput.value = '';
                    };

                    const loadLibrary = async () => {
                        libraryMeta.textContent = 'Loading library...';

                        try {
                            const params = new URLSearchParams({
                                page: String(page),
                                search: librarySearch?.value || '',
                            });

                            if (imagesOnly) {
                                params.set('images_only', '1');
                            }

                            const response = await fetch(`${pickerUrl}?${params.toString()}`, {
                                headers: { Accept: 'application/json' },
                                credentials: 'same-origin',
                            });

                            const payload = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                const message = payload.message || 'Could not load media library.';
                                libraryGrid.innerHTML = `<p class="col-span-full py-8 text-center text-sm text-danger">${message}</p>`;
                                libraryMeta.textContent = '';
                                setError(message);
                                return;
                            }

                            const items = payload.data || [];
                            const pagination = payload.meta || { current_page: 1, last_page: 1, total: items.length };

                            renderLibraryItems(
                                items,
                                pagination,
                                librarySearch?.value ? 'No files match your search.' : 'No files in the library yet. Upload a file first.',
                            );

                            if (libraryPagination) {
                                const showPagination = pagination.last_page > 1;
                                libraryPagination.hidden = !showPagination;
                                if (libraryPrev) {
                                    libraryPrev.disabled = pagination.current_page <= 1;
                                }
                                if (libraryNext) {
                                    libraryNext.disabled = pagination.current_page >= pagination.last_page;
                                }
                            }

                            libraryLoaded = true;
                            setError('');
                        } catch (error) {
                            const message = 'Could not load media library.';
                            libraryGrid.innerHTML = `<p class="col-span-full py-8 text-center text-sm text-danger">${message}</p>`;
                            libraryMeta.textContent = '';
                            setError(message);
                        }
                    };

                    const activateTab = (target) => {
                        root.querySelectorAll('[data-attach-tab]').forEach((el) => {
                            el.classList.toggle('is-active', el.dataset.attachTab === target);
                        });
                        root.querySelectorAll('[data-attach-panel]').forEach((panel) => {
                            panel.classList.toggle('hidden', panel.dataset.attachPanel !== target);
                        });

                        if (target === 'library' && !libraryLoaded) {
                            page = 1;
                            loadLibrary();
                        }
                    };

                    root.querySelectorAll('[data-attach-tab]').forEach((tab) => {
                        tab.addEventListener('click', () => activateTab(tab.dataset.attachTab));
                    });

                    dropzone.addEventListener('click', () => fileInput.click());

                    dropzone.addEventListener('dragover', (event) => {
                        event.preventDefault();
                        dropzone.classList.add('is-dragover');
                    });

                    dropzone.addEventListener('dragleave', () => {
                        dropzone.classList.remove('is-dragover');
                    });

                    dropzone.addEventListener('drop', (event) => {
                        event.preventDefault();
                        dropzone.classList.remove('is-dragover');
                        const file = event.dataTransfer?.files?.[0];
                        if (file) {
                            uploadFile(file);
                        }
                    });

                    fileInput.addEventListener('change', () => {
                        const file = fileInput.files?.[0];
                        if (file) {
                            uploadFile(file);
                        }
                        fileInput.value = '';
                    });

                    root.querySelector('[data-attach-import]').addEventListener('click', importUrlFile);

                    list.addEventListener('click', (event) => {
                        if (event.target.matches('[data-image-remove]')) {
                            event.target.closest('[data-image-item]')?.remove();
                            libraryLoaded = false;
                            root.dispatchEvent(new CustomEvent('images-reordered', { bubbles: true }));
                        }
                    });

                    let draggedItem = null;

                    list.addEventListener('dragstart', (event) => {
                        const item = event.target.closest('[data-image-item]');

                        if (!item) {
                            return;
                        }

                        draggedItem = item;
                        event.dataTransfer.effectAllowed = 'move';
                        item.classList.add('is-dragging');
                    });

                    list.addEventListener('dragover', (event) => {
                        event.preventDefault();

                        const item = event.target.closest('[data-image-item]');

                        if (!item || !draggedItem || item === draggedItem) {
                            return;
                        }

                        const rect = item.getBoundingClientRect();
                        const after = event.clientX > rect.left + rect.width / 2;
                        list.insertBefore(draggedItem, after ? item.nextSibling : item);
                    });

                    list.addEventListener('dragend', () => {
                        draggedItem?.classList.remove('is-dragging');
                        draggedItem = null;
                        root.dispatchEvent(new CustomEvent('images-reordered', { bubbles: true }));
                    });

                    if (librarySearch) {
                        librarySearch.addEventListener('input', () => {
                            window.clearTimeout(searchTimer);
                            searchTimer = window.setTimeout(() => {
                                page = 1;
                                libraryLoaded = false;
                                loadLibrary();
                            }, 250);
                        });
                    }

                    if (libraryPrev) {
                        libraryPrev.addEventListener('click', () => {
                            if (page > 1) {
                                page -= 1;
                                loadLibrary();
                            }
                        });
                    }

                    if (libraryNext) {
                        libraryNext.addEventListener('click', () => {
                            page += 1;
                            loadLibrary();
                        });
                    }
                });
            });
        </script>
    @endpush
@endonce
