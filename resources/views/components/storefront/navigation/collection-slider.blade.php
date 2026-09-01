@props([
    'collections' => [],
    'activeCollection' => null,
])

@if (filled($collections))
    <nav class="storefront-category-slider storefront-collection-slider" aria-label="{{ __('storefront::storefront.filter_collection') }}" data-category-slider>
        <button type="button" class="storefront-category-slider__arrow" data-category-slider-prev aria-label="{{ __('storefront::storefront.scroll_prev') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="m15 18-6-6 6-6" />
            </svg>
        </button>

        <div class="storefront-category-slider__track" data-category-slider-track>
            @foreach ($collections as $collection)
                <a
                    href="{{ route('storefront.catalog.collections.show', $collection->slug) }}"
                    class="storefront-category-slider__pill {{ $activeCollection === $collection->slug ? 'storefront-category-slider__pill--active' : '' }}"
                >
                    <span>{{ $collection->name }}</span>
                </a>
            @endforeach
        </div>

        <button type="button" class="storefront-category-slider__arrow" data-category-slider-next aria-label="{{ __('storefront::storefront.scroll_next') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="m9 18 6-6-6-6" />
            </svg>
        </button>
    </nav>
@endif
