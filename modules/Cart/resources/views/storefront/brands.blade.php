@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.brands_archive_title'))
@section('main_class', 'storefront-shop-main')

@push('head')
    <x-storefront.seo-meta :meta="$pageSeo ?? null" />
    <x-storefront.json-ld :data="$structuredData ?? null" />
    @vite(['resources/css/storefront/shop.css', 'resources/js/storefront/brands.js'])
@endpush

@section('content')
    <x-storefront.layout.page-container
        class="storefront-shop storefront-brands"
        data-brands
        data-brands-count="{{ __('storefront::storefront.brands_search_count', ['count' => '__COUNT__']) }}"
    >
        <header class="storefront-shop__context">
            <h1 class="storefront-shop__context-title">{{ __('storefront::storefront.brands_archive_title') }}</h1>
            <p class="storefront-shop__context-description">{{ __('storefront::storefront.brands_archive_subtitle') }}</p>
        </header>

        @if ($brands->isEmpty())
            <x-storefront.empty-state :title="__('storefront::storefront.brands_empty')" />
        @else
            <div class="storefront-brands__chrome">
                <div class="storefront-shop-toolbar storefront-brands__toolbar">
                    <p class="storefront-shop-toolbar__count" data-brands-search-count aria-live="polite">
                        {{ trans_choice('storefront::storefront.brands_available', $brands->count(), ['count' => $brands->count()]) }}
                    </p>
                    <x-storefront.brands.search />
                </div>
                <x-storefront.brands.alpha-nav :letters="$letters" />
            </div>

            <div class="storefront-brands__directory" data-brands-directory>
                @foreach ($groups as $letter => $letterBrands)
                    @php
                        $letterId = \Commerce\Cart\Services\StorefrontBrandDirectory::letterAnchor((string) $letter);
                        $letterCount = count($letterBrands);
                    @endphp
                    <section
                        class="storefront-brands__section"
                        id="brand-letter-{{ $letterId }}"
                        data-brands-section
                        data-brand-letter="{{ $letter }}"
                    >
                        <h2 class="storefront-brands__letter">{{ $letter }} · {{ $letterCount }}</h2>
                        <ul class="storefront-brands__grid">
                            @foreach ($letterBrands as $brand)
                                <li>
                                    <x-storefront.cards.brand
                                        :name="$brand->name"
                                        :slug="$brand->slug"
                                        :url="$brand->url"
                                        :product-count="$brand->productCount"
                                        :logo-url="$brand->logoUrl"
                                        :monogram="$brand->monogram"
                                        :letter="$brand->letter"
                                    />
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>

            <ul class="storefront-brands__grid" data-brands-results hidden></ul>

            <div class="storefront-brands__empty" data-brands-empty hidden>
                <x-storefront.empty-state :title="__('storefront::storefront.brands_search_empty')">
                    <button type="button" class="storefront-filters__apply" data-brands-clear>
                        {{ __('storefront::storefront.brands_clear_search') }}
                    </button>
                </x-storefront.empty-state>
            </div>
        @endif
    </x-storefront.layout.page-container>
@endsection
