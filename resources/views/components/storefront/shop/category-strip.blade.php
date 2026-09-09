@props([
    'filters',
    'categories' => [],
])

@php
    use Commerce\Cart\DTO\ShopCategoryStripData;
    use Commerce\Cart\DTO\ShopListingFilters;

    $filters = $filters instanceof ShopListingFilters ? $filters : new ShopListingFilters();
    $strip = ShopCategoryStripData::for($filters->category, is_array($categories) ? $categories : []);
@endphp

@if ($strip->items !== [] || $strip->showBack)
    <nav class="storefront-shop-category-strip" aria-label="{{ __('storefront::storefront.nav_categories') }}">
        @if ($strip->showBack)
            <a
                href="{{ route('storefront.shop.index', $filters->queryWith(['category' => null])) }}"
                class="storefront-shop-category-strip__back"
            >
                {{ __('storefront::storefront.shop_all_categories') }}
            </a>
        @endif

        @foreach ($strip->items as $item)
            <a
                href="{{ route('storefront.shop.index', $filters->queryWith(['category' => $item->slug])) }}"
                @class([
                    'storefront-shop-category-strip__link',
                    'storefront-shop-category-strip__link--active' => $filters->category === $item->slug,
                ])
            >
                {{ $item->name }}
            </a>
        @endforeach
    </nav>
@endif
