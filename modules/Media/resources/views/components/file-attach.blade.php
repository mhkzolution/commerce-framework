@props([
    'name' => 'media_uuid',
    'value' => null,
    'label' => 'Attach file',
    'folderUuid' => null,
    'imagesOnly' => false,
    'accept' => null,
    'help' => null,
])

@php
    $query = app(\Commerce\Contracts\Media\MediaQueryServiceInterface::class);
    $previewUrl = $value ? ($query->getUrl($value, 'thumbnail') ?? $query->getUrl($value)) : null;
    $attachId = 'file-attach-' . md5($name . ($value ?? '') . ($folderUuid ?? ''));
    $acceptAttr = $accept ?? ($imagesOnly ? 'image/*' : implode(',', array_map(
        static fn (string $mime): string => match ($mime) {
            'image/jpeg' => '.jpg,.jpeg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
            'image/svg+xml' => '.svg',
            'application/pdf' => '.pdf',
            default => '',
        },
        config('media.allowed_mimes', []),
    )));
@endphp

<div
    id="{{ $attachId }}"
    class="cf-file-attach"
    data-file-attach
    data-upload-url="{{ route('admin.media.store') }}"
    data-import-url="{{ route('admin.media.import') }}"
    data-picker-url="{{ route('admin.media.picker') }}"
    data-folder-uuid="{{ $folderUuid }}"
    data-images-only="{{ $imagesOnly ? '1' : '0' }}"
>
    <label class="block text-sm font-medium text-text">{{ $label }}</label>

    @if ($help)
        <p class="mt-1 text-sm text-muted">{{ $help }}</p>
    @endif

    <input type="hidden" name="{{ $name }}" value="{{ old($name, $value) }}" data-attach-input>

    <div class="mt-3 flex items-start gap-4">
        <div
            class="cf-file-attach__preview flex h-28 w-28 items-center justify-center overflow-hidden rounded-lg border border-border bg-surface-muted"
            data-attach-preview
        >
            @if ($previewUrl)
                <img src="{{ $previewUrl }}" alt="Attached file" class="h-full w-full object-cover">
            @else
                <span class="px-2 text-center text-xs text-muted">No file attached</span>
            @endif
        </div>

        <div class="min-w-0 flex-1 space-y-3">
            <div class="flex flex-wrap gap-2" role="tablist">
                <button type="button" class="cf-file-attach__tab is-active" data-attach-tab="upload">Upload</button>
                <button type="button" class="cf-file-attach__tab" data-attach-tab="url">From URL</button>
                <button type="button" class="cf-file-attach__tab" data-attach-tab="library">Library</button>
            </div>

            <div data-attach-panel="upload">
                <div class="cf-file-attach__dropzone" data-attach-dropzone>
                    <input
                        type="file"
                        class="cf-file-attach__file-input"
                        data-attach-file
                        accept="{{ $acceptAttr }}"
                    >
                    <p class="text-sm font-medium text-text">Drag & drop a file here</p>
                    <p class="mt-1 text-xs text-muted">or click to browse</p>
                </div>
            </div>

            <div class="hidden" data-attach-panel="url">
                <div class="flex flex-wrap gap-2">
                    <input type="url" class="cf-input min-w-[12rem] flex-1" placeholder="https://example.com/file.png" data-attach-url>
                    <button type="button" class="cf-btn cf-btn-secondary" data-attach-import>Import</button>
                </div>
            </div>

            <div class="hidden space-y-3" data-attach-panel="library">
                <input type="search" placeholder="Search library..." class="cf-input" data-attach-library-search>
                <div
                    class="grid max-h-80 grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-4 md:grid-cols-5"
                    data-attach-library-grid
                ></div>
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-muted">
                    <p data-attach-library-meta>Loading library...</p>
                    <div class="flex gap-2" data-attach-library-pagination hidden>
                        <button type="button" class="cf-btn cf-btn-secondary px-2 py-1 text-xs" data-attach-library-prev>Previous</button>
                        <button type="button" class="cf-btn cf-btn-secondary px-2 py-1 text-xs" data-attach-library-next>Next</button>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <p class="text-sm text-danger hidden" data-attach-error></p>
                <p class="text-sm text-muted hidden" data-attach-status></p>
                <button type="button" class="text-sm text-muted hover:text-text" data-attach-clear>Clear</button>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-file-attach]').forEach((root) => {
                    const input = root.querySelector('[data-attach-input]');
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
                    const folderUuid = root.dataset.folderUuid || '';
                    const imagesOnly = root.dataset.imagesOnly === '1';
                    const csrf = @json(csrf_token());
                    let page = 1;
                    let libraryLoaded = false;
                    let searchTimer = null;

                    const setError = (message) => {
                        errorEl.textContent = message || '';
                        errorEl.classList.toggle('hidden', !message);
                    };

                    const setStatus = (message) => {
                        statusEl.textContent = message || '';
                        statusEl.classList.toggle('hidden', !message);
                    };

                    const renderPreview = (item) => {
                        if (!item) {
                            preview.innerHTML = '<span class="px-2 text-center text-xs text-muted">No file attached</span>';
                            return;
                        }

                        const url = item.preview_url || item.url;
                        const isImage = (item.mime_type || '').startsWith('image/');

                        preview.innerHTML = isImage && url
                            ? `<img src="${url}" alt="${item.filename || 'Attached file'}" class="h-full w-full object-cover">`
                            : `<div class="px-2 text-center text-xs text-muted"><div class="font-medium">${item.filename || 'File attached'}</div><div class="mt-1">${item.mime_type || ''}</div></div>`;
                    };

                    const applySelection = (item) => {
                        input.value = item.uuid;
                        renderPreview(item);
                        setError('');
                        setStatus('File attached.');
                        root.querySelectorAll('[data-library-item]').forEach((button) => {
                            button.classList.toggle('ring-2', button.dataset.uuid === item.uuid);
                            button.classList.toggle('ring-primary', button.dataset.uuid === item.uuid);
                        });
                    };

                    const renderLibraryItems = (targetGrid, targetMeta, items, pagination, emptyMessage) => {
                        targetGrid.innerHTML = '';

                        if (!items.length) {
                            targetGrid.innerHTML = `<p class="col-span-full py-8 text-center text-sm text-muted">${emptyMessage}</p>`;
                        } else {
                            items.forEach((item) => {
                                const button = document.createElement('button');
                                button.type = 'button';
                                button.dataset.libraryItem = '1';
                                button.dataset.uuid = item.uuid;
                                button.className = 'overflow-hidden rounded-md border border-border text-left hover:border-primary focus:border-primary focus:outline-none';
                                if (input.value === item.uuid) {
                                    button.classList.add('ring-2', 'ring-primary');
                                }
                                button.innerHTML = `
                                    <div class="aspect-square bg-surface-muted flex items-center justify-center">
                                        ${(item.mime_type || '').startsWith('image/') && (item.url || item.preview_url)
                                            ? `<img src="${item.url || item.preview_url}" alt="${item.filename || 'Media'}" class="h-full w-full object-cover" loading="lazy">`
                                            : `<span class="px-2 text-xs text-muted">${item.filename || 'File'}</span>`}
                                    </div>
                                    <div class="truncate p-2 text-xs text-muted">${item.filename || 'Untitled'}</div>
                                `;
                                button.addEventListener('click', () => {
                                    applySelection(item);
                                });
                                targetGrid.appendChild(button);
                            });
                        }

                        if (pagination) {
                            targetMeta.textContent = `Page ${pagination.current_page} of ${pagination.last_page} · ${pagination.total} items`;
                        }
                    };

                    const uploadFile = async (file) => {
                        setError('');
                        setStatus('Uploading...');

                        const formData = new FormData();
                        formData.append('file', file);
                        if (folderUuid) {
                            formData.append('folder_uuid', folderUuid);
                        }

                        const response = await fetch(uploadUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
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

                        applySelection(payload.data);
                        libraryLoaded = false;
                    };

                    const importUrlFile = async () => {
                        setError('');
                        setStatus('Importing...');

                        const response = await fetch(importUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({
                                url: urlInput.value,
                                folder_uuid: folderUuid || null,
                            }),
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            setStatus('');
                            setError(payload.message || 'Import failed.');
                            return;
                        }

                        applySelection(payload.data);
                        urlInput.value = '';
                        libraryLoaded = false;
                    };

                    const loadLibrary = async () => {
                        const targetGrid = libraryGrid;
                        const targetMeta = libraryMeta;
                        const searchValue = librarySearch?.value || '';

                        if (!targetGrid || !targetMeta) {
                            return;
                        }

                        targetMeta.textContent = 'Loading library...';

                        try {
                            const params = new URLSearchParams({
                                images_only: imagesOnly ? '1' : '0',
                                page: String(page),
                                search: searchValue || '',
                            });

                            const response = await fetch(`${pickerUrl}?${params.toString()}`, {
                                headers: { 'Accept': 'application/json' },
                                credentials: 'same-origin',
                            });

                            const payload = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                const message = payload.message || 'Could not load media library.';
                                targetGrid.innerHTML = `<p class="col-span-full py-8 text-center text-sm text-danger">${message}</p>`;
                                targetMeta.textContent = '';
                                setError(message);
                                return;
                            }

                            const items = payload.data || [];
                            const pagination = payload.meta || { current_page: 1, last_page: 1, total: items.length };

                            renderLibraryItems(
                                targetGrid,
                                targetMeta,
                                items,
                                pagination,
                                searchValue ? 'No files match your search.' : 'No files in the library yet. Upload a file first.',
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
                            targetGrid.innerHTML = `<p class="col-span-full py-8 text-center text-sm text-danger">${message}</p>`;
                            targetMeta.textContent = '';
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
                        tab.addEventListener('click', () => {
                            activateTab(tab.dataset.attachTab);
                        });
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
                    root.querySelector('[data-attach-clear]').addEventListener('click', () => {
                        input.value = '';
                        renderPreview(null);
                        setError('');
                        setStatus('');
                        root.querySelectorAll('[data-library-item]').forEach((button) => {
                            button.classList.remove('ring-2', 'ring-primary');
                        });
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
