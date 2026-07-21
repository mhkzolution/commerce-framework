@props([
    'name' => 'media_uuid',
    'value' => null,
    'label' => 'Media',
])

@php
    $previewUrl = null;
    if ($value) {
        $previewUrl = app(\Commerce\Contracts\Media\MediaQueryServiceInterface::class)->getUrl($value, 'thumbnail')
            ?? app(\Commerce\Contracts\Media\MediaQueryServiceInterface::class)->getUrl($value);
    }
    $pickerId = 'media-picker-' . md5($name . ($value ?? ''));
@endphp

<div id="{{ $pickerId }}" class="space-y-3" data-picker-root>
    <label class="block text-sm font-medium text-text">{{ $label }}</label>

    <input type="hidden" name="{{ $name }}" value="{{ old($name, $value) }}" data-picker-input>

    <div class="flex items-start gap-4">
        <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-md border border-border bg-surface-muted" data-picker-preview>
            @if ($previewUrl)
                <img src="{{ $previewUrl }}" alt="Selected media" class="h-full w-full object-cover">
            @else
                <span class="text-xs text-muted">No image</span>
            @endif
        </div>

        <div class="space-y-2">
            <button type="button" class="cf-btn cf-btn-primary" data-picker-open>
                Choose from library
            </button>
            <button type="button" class="block text-sm text-muted hover:text-text" data-picker-clear>
                Clear selection
            </button>
        </div>
    </div>

    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" data-picker-modal>
        <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg border border-border bg-surface shadow-xl">
            <div class="flex items-center justify-between border-b border-border px-4 py-3">
                <h3 class="text-lg font-medium text-text">Select media</h3>
                <button type="button" class="text-muted hover:text-text" data-picker-close>&times;</button>
            </div>
            <div class="border-b border-border px-4 py-3">
                <input type="search" placeholder="Search images..." class="cf-input" data-picker-search>
            </div>
            <div class="grid flex-1 grid-cols-2 gap-3 overflow-y-auto p-4 sm:grid-cols-3 md:grid-cols-4" data-picker-grid></div>
            <div class="border-t border-border px-4 py-3 text-sm text-muted" data-picker-meta></div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const pickerUrl = @json(route('admin.media.picker'));

                document.querySelectorAll('[data-picker-root]').forEach((root) => {
                    const input = root.querySelector('[data-picker-input]');
                    const preview = root.querySelector('[data-picker-preview]');
                    const modal = root.querySelector('[data-picker-modal]');
                    const grid = root.querySelector('[data-picker-grid]');
                    const meta = root.querySelector('[data-picker-meta]');
                    const search = root.querySelector('[data-picker-search]');
                    let page = 1;

                    const renderPreview = (url) => {
                        preview.innerHTML = url
                            ? `<img src="${url}" alt="Selected media" class="h-full w-full object-cover">`
                            : '<span class="text-xs text-muted">No image</span>';
                    };

                    const loadItems = async () => {
                        const params = new URLSearchParams({
                            images_only: '1',
                            page: String(page),
                            search: search.value || '',
                        });

                        const response = await fetch(`${pickerUrl}?${params.toString()}`, {
                            headers: { 'Accept': 'application/json' },
                        });
                        const payload = await response.json();
                        grid.innerHTML = '';

                        payload.data.forEach((item) => {
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'overflow-hidden rounded-md border border-border text-left hover:border-primary';
                            button.innerHTML = `
                                <div class="aspect-square bg-surface-muted">
                                    <img src="${item.url || item.preview_url}" alt="${item.filename}" class="h-full w-full object-cover">
                                </div>
                                <div class="truncate p-2 text-xs text-muted">${item.filename}</div>
                            `;
                            button.addEventListener('click', () => {
                                input.value = item.uuid;
                                renderPreview(item.url || item.preview_url);
                                modal.classList.add('hidden');
                                modal.classList.remove('flex');
                            });
                            grid.appendChild(button);
                        });

                        meta.textContent = `Page ${payload.meta.current_page} of ${payload.meta.last_page} · ${payload.meta.total} items`;
                    };

                    root.querySelector('[data-picker-open]').addEventListener('click', () => {
                        page = 1;
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        loadItems();
                    });

                    root.querySelector('[data-picker-close]').addEventListener('click', () => {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    });

                    root.querySelector('[data-picker-clear]').addEventListener('click', () => {
                        input.value = '';
                        renderPreview(null);
                    });

                    search.addEventListener('input', () => {
                        page = 1;
                        loadItems();
                    });

                    modal.addEventListener('click', (event) => {
                        if (event.target === modal) {
                            modal.classList.add('hidden');
                            modal.classList.remove('flex');
                        }
                    });
                });
            });
        </script>
    @endpush
@endonce
