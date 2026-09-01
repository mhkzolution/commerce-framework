@props([
    'categories' => [],
    'categoryImageUrls' => [],
    'activeCategory' => null,
    'showNewProducts' => true,
])

@php
    $isShopHome = request()->routeIs('storefront.shop.index')
        && request()->query('category') === null
        && request()->query('search') === null
        && request()->query('sort', 'newest') === 'newest';
@endphp

<nav class="storefront-category-slider" aria-label="{{ __('storefront::storefront.filter_category') }}" data-category-slider>
    <button type="button" class="storefront-category-slider__arrow" data-category-slider-prev aria-label="{{ __('storefront::storefront.scroll_prev') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="m15 18-6-6 6-6" />
        </svg>
    </button>

    <div class="storefront-category-slider__track" data-category-slider-track>
        @if ($showNewProducts)
            <a
                href="{{ route('storefront.shop.index', ['sort' => 'newest']) }}"
                class="storefront-category-slider__pill {{ $isShopHome ? 'storefront-category-slider__pill--active' : '' }}"
            >
                {{ __('storefront::storefront.new_products') }}
            </a>
        @endif

        @foreach ($categories as $category)
            @continue(! filled($category->slug))
            @php
                $imageUrl = $categoryImageUrls[$category->slug] ?? null;
                $isActive = $activeCategory === $category->slug;
            @endphp
            <a
                href="{{ route('storefront.catalog.categories.show', $category->slug) }}"
                class="storefront-category-slider__pill {{ $isActive ? 'storefront-category-slider__pill--active' : '' }}"
            >
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}" alt="" class="storefront-category-slider__thumb" loading="lazy" decoding="async">
                @endif
                <span>{{ $category->name }}</span>
            </a>
        @endforeach
    </div>

    <button type="button" class="storefront-category-slider__arrow" data-category-slider-next aria-label="{{ __('storefront::storefront.scroll_next') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="m9 18 6-6-6-6" />
        </svg>
    </button>
</nav>
