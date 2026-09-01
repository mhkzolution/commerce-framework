@props([
    'brands' => [],
    'brandLogoUrls' => [],
    'activeBrand' => null,
])

@if (filled($brands))
    <nav class="storefront-category-slider storefront-brand-slider" aria-label="{{ __('storefront::storefront.filter_brand') }}" data-category-slider>
        <button type="button" class="storefront-category-slider__arrow" data-category-slider-prev aria-label="{{ __('storefront::storefront.scroll_prev') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="m15 18-6-6 6-6" />
            </svg>
        </button>

        <div class="storefront-category-slider__track" data-category-slider-track>
            @foreach ($brands as $brand)
                @php
                    $logoUrl = $brandLogoUrls[$brand->slug] ?? null;
                @endphp
                <a
                    href="{{ route('storefront.catalog.brands.show', $brand->slug) }}"
                    class="storefront-category-slider__pill storefront-brand-slider__pill {{ ($activeBrand === $brand->slug || $activeBrand === $brand->uuid) ? 'storefront-category-slider__pill--active' : '' }}"
                >
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="" class="storefront-brand-slider__logo" loading="lazy">
                    @endif
                    <span>{{ $brand->name }}</span>
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
