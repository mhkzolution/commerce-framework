@extends('cart::layouts.storefront')

@php
    $pageTitle = $activeCollection?->name
        ?? $activeCategory?->name
        ?? $activeBrand?->name
        ?? __('storefront::storefront.shop');
@endphp

@section('title', $pageTitle)

@section('content')
    <div
        class="storefront-shop"
        data-shop
        data-shop-url="{{ route('storefront.shop.index') }}"
        data-infinite-scroll="true"
    >
        @if (! empty($breadcrumbItems))
            <div class="storefront-shop__breadcrumb">
                <x-storefront.breadcrumb :items="$breadcrumbItems" />
            </div>
        @endif

        @if ($activeCollection || $activeCategory || $activeBrand)
            <header class="storefront-shop__context">
                <h1 class="storefront-shop__context-title">{{ $pageTitle }}</h1>
                @if ($activeCollection?->description)
                    <p class="storefront-shop__context-description">{{ $activeCollection->description }}</p>
                @elseif ($activeCategory?->description)
                    <p class="storefront-shop__context-description">{{ $activeCategory->description }}</p>
                @elseif ($activeBrand?->description)
                    <p class="storefront-shop__context-description">{{ $activeBrand->description }}</p>
                @endif
            </header>
        @endif

        <x-storefront.shop-toolbar
            :filters="$filters"
            :total="$products->total()"
        />

        <x-storefront.active-filters
            :filters="$filters"
            :categories="$categories"
            :collections="$collections ?? collect()"
            :brands="$brands"
            :filter-catalog="$filterCatalog"
        />

        <div class="storefront-shop__layout">
            <x-storefront.filters-sidebar
                :filters="$filters"
                :filter-catalog="$filterCatalog"
                :categories="$categories"
                :filter-categories="$filterCategories"
                :brands="$brands"
                :category-image-urls="$categoryImageUrls"
                :brand-logo-urls="$brandLogoUrls"
            />

            <div class="storefront-shop__results" data-shop-results>
                @include('cart::storefront.partials.product-results')
            </div>
        </div>

        <x-storefront.filters-sheet
            :filters="$filters"
            :filter-catalog="$filterCatalog"
            :categories="$categories"
            :filter-categories="$filterCategories"
            :brands="$brands"
            :category-image-urls="$categoryImageUrls"
            :brand-logo-urls="$brandLogoUrls"
        />
    </div>
@endsection

@push('scripts')
    @vite('resources/js/storefront/shop.js')
@endpush
