@extends('cart::layouts.storefront')

@section('title', 'Shop')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-text">Shop</h1>
            <p class="mt-1 text-sm text-muted">Published products</p>
        </div>
        <form method="GET" class="max-w-sm flex-1">
            <x-admin.search-input name="search" placeholder="Search products..." :value="$search" />
        </form>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($products as $product)
            <x-storefront.cards.product
                :product="$product"
                :display-currency="$displayCurrency"
                :base-currency="$baseCurrency"
                :currency-converter="$currencyConverter"
                :quick-add="true"
            />
        @empty
            <p class="col-span-full text-center text-muted">{{ __('storefront::storefront.no_products') }}</p>
        @endforelse
    </div>

    @if ($products->hasPages())
        <div class="mt-8">{{ $products->withQueryString()->links() }}</div>
    @endif
@endsection
