@extends('cart::layouts.storefront')

@section('title', $pageSeo['title'] ?? $landing['title'] ?? __('storefront::storefront.shop'))

@push('head')
    <x-storefront.seo-meta :meta="$pageSeo" />
@endpush

@section('content')
    <div
        class="storefront-shop storefront-catalog-landing"
        data-shop
        data-shop-url="{{ $landing['shopUrl'] ?? route('storefront.shop.index') }}"
        data-infinite-scroll="true"
    >
        <div class="storefront-catalog-landing__breadcrumb">
            <x-storefront.breadcrumb :items="$breadcrumbItems ?? []" />
        </div>

        @include('cart::storefront.partials.catalog-landing-hero')

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
