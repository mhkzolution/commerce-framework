@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.shop'))
@section('main_class', 'storefront-shop-main')

@push('head')
    @vite('resources/css/storefront/shop.css')
@endpush

@section('content')
    <x-storefront.layout.page-container class="storefront-shop">
        <header class="storefront-shop__header">
            <h1 class="storefront-shop__title">{{ __('storefront::storefront.shop') }}</h1>

            <form method="GET" action="{{ route('storefront.shop.index') }}" class="storefront-shop-filters">
                @foreach (collect($filters->toQueryArray())->except(['search', 'category', 'availability']) as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach

                <label class="storefront-shop-filters__field">
                    <span class="storefront-shop-filters__label">{{ __('storefront::storefront.filter_category') }}</span>
                    <select name="category" class="storefront-shop-filters__select" onchange="this.form.submit()">
                        <option value="">{{ __('storefront::storefront.filter_all') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected($filters->category === $category->slug)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="storefront-shop-filters__field">
                    <span class="storefront-shop-filters__label">{{ __('storefront::storefront.filter_availability') }}</span>
                    <select name="availability" class="storefront-shop-filters__select" onchange="this.form.submit()">
                        <option value="all" @selected($filters->availability === 'all')>{{ __('storefront::storefront.availability_all') }}</option>
                        <option value="in_stock" @selected($filters->availability === 'in_stock')>{{ __('storefront::storefront.availability_in_stock') }}</option>
                    </select>
                </label>

                <label class="storefront-shop-filters__field storefront-shop-filters__field--search">
                    <span class="storefront-shop-filters__label">{{ __('storefront::storefront.search') }}</span>
                    <input
                        type="search"
                        name="search"
                        value="{{ $filters->search }}"
                        placeholder="{{ __('storefront::storefront.search_placeholder') }}"
                        class="storefront-shop-search"
                    >
                </label>
            </form>
        </header>

        <x-storefront.shop.toolbar
            :count="$products->total()"
            :sort="$filters->sort"
            :query="$filters->toQueryArray()"
        />

        <div class="storefront-shop__grid">
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
            <div class="storefront-shop__pagination">{{ $products->withQueryString()->links('pagination::storefront') }}</div>
        @endif
    </x-storefront.layout.page-container>
@endsection
