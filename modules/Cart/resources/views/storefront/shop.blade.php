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
            @php $variant = $product->defaultVariant(); @endphp
            @if ($variant)
                <article class="rounded-lg border border-border bg-surface p-4 shadow-sm">
                    <h2 class="font-medium text-text">
                        <a href="{{ route('storefront.products.show', $product->slug) }}" class="hover:text-primary">
                            {{ $product->name }}
                        </a>
                    </h2>
                    <p class="mt-1 text-sm text-muted">{{ $product->slug }}</p>
                    <p class="mt-3 text-lg font-semibold text-text">
                        @php
                            $displayPrice = $variant->price;
                            if ($currencyConverter && $displayCurrency !== $baseCurrency) {
                                $displayPrice = $currencyConverter->convert($displayPrice, $baseCurrency, $displayCurrency);
                            }
                        @endphp
                        {{ number_format($displayPrice / 100, 2) }} {{ $displayCurrency }}
                    </p>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('storefront.products.show', $product->slug) }}" class="cf-btn cf-btn--secondary flex-1 text-center">View</a>
                        <form method="POST" action="{{ route('storefront.cart.items.store') }}" class="flex flex-1 gap-2">
                            @csrf
                            <input type="hidden" name="purchasable_uuid" value="{{ $variant->uuid }}">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="cf-btn cf-btn--primary w-full">Add</button>
                        </form>
                    </div>
                </article>
            @endif
        @empty
            <p class="col-span-full text-center text-muted">No products available.</p>
        @endforelse
    </div>

    @if ($products->hasPages())
        <div class="mt-8">{{ $products->withQueryString()->links() }}</div>
    @endif
@endsection
