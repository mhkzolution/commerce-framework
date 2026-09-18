@extends('cart::layouts.storefront')

@php
    use Commerce\Cart\DTO\ShopListingContext;
    use Commerce\Catalog\Models\Brand;

    $listing = ($listing ?? null) instanceof ShopListingContext ? $listing : ShopListingContext::shop();
    $listingBrand = ($listingBrand ?? null) instanceof Brand ? $listingBrand : null;
    $pageTitle = __('storefront::storefront.shop');

    if ($listingBrand !== null) {
        $pageTitle = (string) $listingBrand->name;
    } elseif (is_string($filters->search) && $filters->search !== '') {
        $pageTitle = $filters->search;
    } elseif (is_string($filters->category) && $filters->category !== '') {
        $stack = $categories;
        while ($stack !== []) {
            $category = array_shift($stack);
            if ($category->slug === $filters->category) {
                $pageTitle = $category->name;
                break;
            }
            foreach ($category->children as $child) {
                $stack[] = $child;
            }
        }
    }

    $showContext = $listingBrand !== null || $filters->hasListingConstraints();
    $contextDescription = $listingBrand !== null
        ? trans_choice('storefront::storefront.brand_products_found', $products->total(), ['count' => $products->total()])
        : null;
    $emptyTitle = $listingBrand !== null
        ? __('storefront::storefront.brand_empty_title')
        : __('storefront::storefront.no_products');
@endphp

@section('title', $pageTitle)
@section('main_class', 'storefront-shop-main')

@push('head')
    <x-storefront.seo-meta :meta="$pageSeo ?? null" />
    <x-storefront.json-ld :data="$structuredData ?? null" />
    @vite(['resources/css/storefront/shop.css', 'resources/js/storefront/shop.js'])
@endpush

@section('content')
    <x-storefront.layout.page-container
        class="storefront-shop"
        data-shop
        data-shop-url="{{ $listing->url() }}"
    >
        @if ($breadcrumbItems !== [])
            <div class="storefront-shop__breadcrumb">
                <x-storefront.breadcrumb :items="$breadcrumbItems" :aria-label="__('storefront::storefront.breadcrumb')" />
            </div>
        @endif

        <x-storefront.shop.category-strip
            :filters="$filters"
            :categories="$categories"
            :listing="$listing"
        />

        @if ($showContext)
            <header class="storefront-shop__context">
                <h1 class="storefront-shop__context-title">{{ $pageTitle }}</h1>
                @if ($contextDescription)
                    <p class="storefront-shop__context-description">{{ $contextDescription }}</p>
                @endif
            </header>
        @endif

        <x-storefront.shop.toolbar
            :count="$products->total()"
            :sort="$filters->sort"
            :query="$listing->query($filters)"
            :listing="$listing"
        />

        <x-storefront.shop.active-filters
            :filters="$filters"
            :categories="$categories"
            :filter-catalog="$filterCatalog"
            :listing="$listing"
        />

        <div class="storefront-shop__layout">
            <x-storefront.shop.filters-sidebar
                class="storefront-shop-filters-sidebar"
                :filters="$filters"
                :filter-catalog="$filterCatalog"
                :listing="$listing"
            />

            <div class="storefront-shop__results" data-shop-results>
                <div class="storefront-product-grid storefront-product-grid--shop storefront-shop__grid" data-shop-grid>
                    @forelse ($products as $product)
                        <x-storefront.cards.product
                            :product="$product"
                            :display-currency="$displayCurrency"
                            :base-currency="$baseCurrency"
                            :currency-converter="$currencyConverter"
                            :quick-add="true"
                        />
                    @empty
                        <x-storefront.empty-state :title="$emptyTitle">
                            @if ($listingBrand !== null)
                                <a href="{{ route('storefront.shop.index') }}" class="storefront-filters__apply">
                                    {{ __('storefront::storefront.brand_browse_all') }}
                                </a>
                            @endif
                        </x-storefront.empty-state>
                    @endforelse
                </div>

                @if ($products->hasPages())
                    <div class="storefront-shop__pagination" data-shop-pagination>
                        {{ $products->withQueryString()->links('pagination::storefront') }}
                    </div>
                    <div class="storefront-shop__infinite-sentinel" data-shop-infinite-sentinel aria-hidden="true"></div>
                @endif
            </div>
        </div>

        <x-storefront.shop.filters-sheet
            :filters="$filters"
            :filter-catalog="$filterCatalog"
            :listing="$listing"
        />
    </x-storefront.layout.page-container>
@endsection
