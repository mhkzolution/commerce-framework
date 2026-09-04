@props([
    'items' => [],
])

@php
    $items = array_values($items);
    $initial = $items[0] ?? null;
@endphp

<div {{ $attributes->merge(['class' => 'storefront-gallery']) }} data-product-gallery>
    <div class="storefront-gallery__stage" data-gallery-stage>
        @if ($initial)
            @if (($initial['type'] ?? 'image') === 'video')
                <video
                    class="storefront-gallery__video"
                    data-gallery-main
                    data-gallery-type="video"
                    controls
                    playsinline
                    poster="{{ $initial['thumbnail'] ?? '' }}"
                >
                    <source src="{{ $initial['url'] }}" type="video/mp4">
                </video>
            @else
                <button
                    type="button"
                    class="storefront-gallery__zoom"
                    data-gallery-zoom
                    data-gallery-lightbox-trigger
                    aria-label="{{ __('storefront::storefront.enlarge_image') }}"
                >
                    <img
                        src="{{ $initial['url'] }}"
                        alt="{{ $initial['alt'] ?? '' }}"
                        class="storefront-gallery__image"
                        data-gallery-main
                        data-gallery-type="image"
                    >
                </button>
            @endif
        @else
            <div class="storefront-gallery__placeholder">{{ __('storefront::storefront.no_image') }}</div>
        @endif
    </div>

    @if (count($items) > 1)
        <div class="storefront-gallery__thumbs" data-gallery-thumbs>
            @foreach ($items as $index => $item)
                <button
                    type="button"
                    class="storefront-gallery__thumb {{ $index === 0 ? 'storefront-gallery__thumb--active' : '' }}"
                    data-gallery-thumb
                    data-gallery-index="{{ $index }}"
                    data-gallery-type="{{ $item['type'] ?? 'image' }}"
                    data-gallery-url="{{ $item['url'] }}"
                    data-gallery-alt="{{ $item['alt'] ?? '' }}"
                    data-gallery-poster="{{ $item['thumbnail'] ?? '' }}"
                    aria-label="{{ $item['alt'] ?? '' }}"
                >
                    @if (($item['type'] ?? 'image') === 'video')
                        <span class="storefront-gallery__thumb-video-badge" aria-hidden="true">▶</span>
                    @endif
                    <img src="{{ $item['thumbnail'] ?? $item['url'] }}" alt="" class="storefront-gallery__thumb-image" loading="lazy">
                </button>
            @endforeach
        </div>
    @endif

    <script type="application/json" data-gallery-items>@json($items)</script>

    <div
        class="storefront-gallery-lightbox"
        data-gallery-lightbox
        hidden
        aria-hidden="true"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('storefront::storefront.enlarge_image') }}"
    >
        <div
            class="storefront-gallery-lightbox__backdrop"
            data-gallery-lightbox-close
            aria-hidden="true"
        ></div>
        <button
            type="button"
            class="storefront-gallery-lightbox__close"
            data-gallery-lightbox-close
            aria-label="{{ __('storefront::storefront.close') }}"
        >
            <span aria-hidden="true">×</span>
        </button>
        <div class="storefront-gallery-lightbox__panel">
            <div class="storefront-gallery-lightbox__content">
                <img src="" alt="" class="storefront-gallery-lightbox__image" data-gallery-lightbox-image>
            </div>
            <div class="storefront-gallery-lightbox__thumbs" data-gallery-lightbox-thumbs hidden></div>
        </div>
    </div>
</div>
