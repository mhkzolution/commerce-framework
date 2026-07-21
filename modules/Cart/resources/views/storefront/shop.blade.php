@extends('cart::layouts.storefront')

@section('title', 'Shop')

@section('content')
    <h1 class="text-2xl font-semibold text-text">Shop</h1>
    <p class="mt-1 text-sm text-muted">Published products</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($products as $product)
            @php $variant = $product->defaultVariant(); @endphp
            @if ($variant)
                <article class="rounded-lg border border-border bg-surface p-4 shadow-sm">
                    <h2 class="font-medium text-text">{{ $product->name }}</h2>
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
                    <form method="POST" action="{{ route('storefront.cart.items.store') }}" class="mt-4 flex gap-2">
                        @csrf
                        <input type="hidden" name="purchasable_uuid" value="{{ $variant->uuid }}">
                        <input type="number" name="quantity" value="1" min="1" class="cf-input w-16 py-1">
                        <button type="submit" class="cf-btn cf-btn--primary flex-1">Add to cart</button>
                    </form>
                </article>
            @endif
        @empty
            <p class="col-span-full text-center text-muted">No products available.</p>
        @endforelse
    </div>

    @if ($products->hasPages())
        <div class="mt-8">{{ $products->links() }}</div>
    @endif
@endsection
