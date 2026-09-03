@props([
    'currentPage' => 1,
    'totalPages' => 1,
    'zoom' => 100,
    'showGuides' => true,
])

<section class="bc-preview" aria-label="{{ __('barcode::admin.preview.title') }}">
    <div class="bc-preview__toolbar">
        <h2 class="bc-panel__title">{{ __('barcode::admin.preview.title') }}</h2>

        <div class="bc-preview__controls">
            <div class="bc-preview__zoom">
                <label for="bc-zoom" class="bc-field-label bc-field-label--sm">{{ __('barcode::admin.preview.zoom') }}</label>
                <input type="range" id="bc-zoom" class="bc-preview__zoom-slider" min="50" max="150" value="{{ $zoom }}" data-bc-zoom>
                <span class="bc-preview__zoom-value" data-bc-zoom-value>{{ $zoom }}%</span>
            </div>

            <div class="bc-preview__pages" data-bc-page-nav>
                <button type="button" class="bc-icon-btn" data-bc-page-prev aria-label="Previous page" disabled>‹</button>
                <span class="bc-preview__page-label" data-bc-page-label>
                    {{ __('barcode::admin.preview.page', ['current' => $currentPage, 'total' => max(1, $totalPages)]) }}
                </span>
                <button type="button" class="bc-icon-btn" data-bc-page-next aria-label="Next page" disabled>›</button>
            </div>

            <button type="button" class="cf-btn cf-btn--ghost cf-btn--sm" data-bc-toggle-guides>
                {{ $showGuides ? __('barcode::admin.preview.hide_guides') : __('barcode::admin.preview.show_guides') }}
            </button>
        </div>
    </div>

    <div class="bc-preview__viewport" data-bc-preview-viewport>
        <div class="bc-preview__canvas-wrap" data-bc-preview-wrap>
            <div
                class="bc-preview__canvas @if($showGuides) bc-preview__canvas--guides @endif"
                data-bc-preview-canvas
                data-bc-show-guides="{{ $showGuides ? 'true' : 'false' }}"
            >
                <div class="bc-preview__page" data-bc-preview-page>
                    {{-- Labels rendered by JS --}}
                </div>
            </div>
        </div>
    </div>

    <footer class="bc-preview__footer">
        <x-admin.button variant="primary" type="button" class="bc-preview__print-btn" data-bc-print>
            {{ __('barcode::admin.preview.print') }}
        </x-admin.button>
    </footer>
</section>
