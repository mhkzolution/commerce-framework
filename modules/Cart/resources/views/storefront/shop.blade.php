@extends('cart::layouts.storefront')

@php
    $pageTitle = __('storefront::storefront.shop');

    if (is_string($filters->search) && $filters->search !== '') {
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
    } elseif (is_string($filters->brand) && $filters->brand !== '') {
        foreach ($filterCatalog->brands as $brand) {
            if ($brand['slug'] === $filters->brand) {
                $pageTitle = $brand['name'];
                break;
            }
        }
    }
@endphp

@section('title', $pageTitle)
@section('main_class', 'storefront-shop-main')

@push('head')
    @vite(['resources/css/storefront/shop.css', 'resources/js/storefront/shop.js'])
@endpush

@section('content')
    <x-storefront.layout.page-container
        class="storefront-shop"
        data-shop
        data-shop-url="{{ route('storefront.shop.index') }}"
    >
        @if ($breadcrumbItems !== [])
            <div class="storefront-shop__breadcrumb">
                <x-storefront.breadcrumb :items="$breadcrumbItems" :aria-label="__('storefront::storefront.breadcrumb')" />
            </div>
        @endif

        <x-storefront.shop.category-strip
            :filters="$filters"
            :categories="$categories"
        />

        @if ($filters->hasListingConstraints())
            <header class="storefront-shop__context">
                <h1 class="storefront-shop__context-title">{{ $pageTitle }}</h1>
            </header>
        @endif

        <x-storefront.shop.toolbar
            :count="$products->total()"
            :sort="$filters->sort"
            :query="$filters->toQueryArray()"
        />

        <x-storefront.shop.active-filters
            :filters="$filters"
            :categories="$categories"
            :filter-catalog="$filterCatalog"
        />

        <div class="storefront-shop__layout">
            <x-storefront.shop.filters-sidebar
                class="storefront-shop-filters-sidebar"
                :filters="$filters"
                :filter-catalog="$filterCatalog"
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
                        <x-storefront.empty-state :title="__('storefront::storefront.no_products')" />
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
        />
    </x-storefront.layout.page-container>
@endsection
