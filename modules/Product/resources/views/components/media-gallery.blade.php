@props([
    'mediaUuids' => [],
    'mediaPreviews' => [],
])

@php
    $galleryId = 'media-gallery-' . uniqid();
@endphp

<div id="{{ $galleryId }}" class="space-y-3" data-gallery-root>
    <label class="block text-sm font-medium text-text">Product images</label>
    <p class="text-xs text-muted">First image is the primary image.</p>

    <div class="flex flex-wrap gap-3" data-gallery-list>
        @foreach ($mediaUuids as $uuid)
            <div class="relative" data-gallery-item="{{ $uuid }}">
                <input type="hidden" name="media_uuids[]" value="{{ $uuid }}">
                <div class="cf-gallery-thumb h-20 w-20 overflow-hidden rounded-md">
                    @if (! empty($mediaPreviews[$uuid]))
                        <img src="{{ $mediaPreviews[$uuid] }}" alt="" class="h-full w-full object-cover">
                    @endif
                </div>
                <button type="button" class="cf-gallery-remove absolute -right-2 -top-2 rounded-full px-1.5 text-xs" data-gallery-remove>&times;</button>
            </div>
        @endforeach
    </div>

    <x-admin.button variant="secondary" type="button" data-gallery-open>
        Add image from library
    </x-admin.button>

    <div class="cf-gallery-overlay fixed inset-0 z-50 hidden items-center justify-center p-4" data-gallery-modal>
        <div class="cf-gallery-panel flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg shadow-md">
            <div class="flex items-center justify-between border-b border-border px-4 py-3">
                <h3 class="text-lg font-medium text-text">Select image</h3>
                <button type="button" class="text-muted hover:text-text" data-gallery-close>&times;</button>
            </div>
            <div class="border-b border-border px-4 py-3">
                <input type="search" placeholder="Search images..." class="cf-input" data-gallery-search>
            </div>
            <div class="grid flex-1 grid-cols-2 gap-3 overflow-y-auto p-4 sm:grid-cols-3 md:grid-cols-4" data-gallery-grid></div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const pickerUrl = @json(route('admin.media.picker'));

                document.querySelectorAll('[data-gallery-root]').forEach((root) => {
                    const list = root.querySelector('[data-gallery-list]');
                    const modal = root.querySelector('[data-gallery-modal]');
                    const grid = root.querySelector('[data-gallery-grid]');
                    const search = root.querySelector('[data-gallery-search]');

                    const existing = () => new Set(
                        [...root.querySelectorAll('[data-gallery-item]')].map((el) => el.dataset.galleryItem)
                    );

                    const loadItems = async () => {
                        const params = new URLSearchParams({ images_only: '1', search: search.value || '' });
                        const response = await fetch(`${pickerUrl}?${params}`, { headers: { Accept: 'application/json' } });
                        const payload = await response.json();
                        grid.innerHTML = '';
                        const taken = existing();

                        payload.data.forEach((item) => {
                            if (taken.has(item.uuid)) return;
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'cf-gallery-picker-item overflow-hidden rounded-md text-left';
                            button.innerHTML = `
                                <div class="cf-gallery-picker-preview aspect-square">
                                    <img src="${item.url || item.preview_url}" alt="${item.filename}" class="h-full w-full object-cover">
                                </div>
                                <div class="truncate p-2 text-xs text-muted">${item.filename}</div>
                            `;
                            button.addEventListener('click', () => {
                                const wrapper = document.createElement('div');
                                wrapper.className = 'relative';
                                wrapper.dataset.galleryItem = item.uuid;
                                wrapper.innerHTML = `
                                    <input type="hidden" name="media_uuids[]" value="${item.uuid}">
                                    <div class="cf-gallery-thumb h-20 w-20 overflow-hidden rounded-md">
                                        <img src="${item.url || item.preview_url}" alt="" class="h-full w-full object-cover">
                                    </div>
                                    <button type="button" class="cf-gallery-remove absolute -right-2 -top-2 rounded-full px-1.5 text-xs" data-gallery-remove>&times;</button>
                                `;
                                list.appendChild(wrapper);
                                modal.classList.add('hidden');
                                modal.classList.remove('flex');
                            });
                            grid.appendChild(button);
                        });
                    };

                    root.querySelector('[data-gallery-open]').addEventListener('click', () => {
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        loadItems();
                    });

                    root.querySelector('[data-gallery-close]').addEventListener('click', () => {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    });

                    search.addEventListener('input', loadItems);

                    list.addEventListener('click', (event) => {
                        if (event.target.matches('[data-gallery-remove]')) {
                            event.target.closest('[data-gallery-item]')?.remove();
                        }
                    });
                });
            });
        </script>
    @endpush
@endonce
