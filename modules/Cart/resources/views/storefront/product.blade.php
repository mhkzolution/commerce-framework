@extends('cart::layouts.storefront')

@section('title', $product->name)

@section('content')
    <nav class="mb-6 text-sm text-muted">
        <a href="{{ route('storefront.shop.index') }}" class="hover:text-text">Shop</a>
        <span class="mx-2">/</span>
        <span class="text-text">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-8 lg:grid-cols-2">
        <div class="rounded-lg border border-border bg-surface p-6">
            @if ($product->media->isNotEmpty())
                <div class="aspect-square rounded-md bg-background"></div>
            @else
                <div class="flex aspect-square items-center justify-center rounded-md bg-background text-muted">No image</div>
            @endif
        </div>

        <div>
            <h1 class="text-3xl font-semibold text-text">{{ $product->name }}</h1>
            @if ($product->description)
                <div class="prose prose-sm mt-4 max-w-none text-text-secondary">{!! nl2br(e($product->description)) !!}</div>
            @endif

            @if ($variant)
                <p class="mt-6 text-2xl font-semibold text-text">
                    @php
                        $displayPrice = $variant->price;
                        if ($currencyConverter && $displayCurrency !== $baseCurrency) {
                            $displayPrice = $currencyConverter->convert($displayPrice, $baseCurrency, $displayCurrency);
                        }
                    @endphp
                    {{ number_format($displayPrice / 100, 2) }} {{ $displayCurrency }}
                </p>

                <p class="mt-2 text-sm text-muted">
                    @if ($available > 0)
                        {{ $available }} in stock
                    @else
                        Out of stock
                    @endif
                    @if ($variant->sku)
                        · SKU {{ $variant->sku }}
                    @endif
                </p>

                @if ($available > 0)
                    <form method="POST" action="{{ route('storefront.cart.items.store') }}" class="mt-6 flex gap-3">
                        @csrf
                        <input type="hidden" name="purchasable_uuid" value="{{ $variant->uuid }}">
                        <input type="number" name="quantity" value="1" min="1" max="{{ $available }}" class="cf-input w-20 py-2">
                        <button type="submit" class="cf-btn cf-btn--primary flex-1">Add to cart</button>
                    </form>
                @endif
            @else
                <p class="mt-6 text-muted">This product is not available.</p>
            @endif
        </div>
    </div>
@endsection
