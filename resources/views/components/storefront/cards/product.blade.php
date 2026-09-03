@props([
    'product',
    'displayCurrency' => '',
    'baseCurrency' => null,
    'currencyConverter' => null,
    'quickAdd' => false,
    'priority' => false,
])

@php
    if (! $product instanceof \Commerce\Contracts\Storefront\ProductCardData) {
        throw new \InvalidArgumentException('x-storefront.cards.product requires ProductCardData.');
    }

    $displayPrice = $product->price;
    if ($currencyConverter && $baseCurrency && $displayCurrency && $displayCurrency !== $baseCurrency) {
        $displayPrice = $currencyConverter->convert($displayPrice, $baseCurrency, $displayCurrency);
    }

    $hookHtml = '';
    if (app()->bound(\Commerce\Contracts\Hook\HookRegistryInterface::class)) {
        $hookHtml = app(\Commerce\Contracts\Hook\HookRegistryInterface::class)->filter(
            'storefront.product.card',
            '',
            ['product' => $product],
        );
    }
@endphp

<article {{ $attributes->merge(['class' => 'storefront-product-card']) }}>
    <a href="{{ $product->url }}" class="storefront-product-card__link">
        <span class="storefront-product-card__media">
            @if ($product->imageUrl)
                <img
                    src="{{ $product->imageUrl }}"
                    alt="{{ $product->name }}"
                    class="storefront-product-card__image"
                    @if ($priority) fetchpriority="high" @else loading="lazy" @endif
                    decoding="async"
                >
            @else
                <span class="storefront-product-card__placeholder"></span>
            @endif
        </span>
        <span class="storefront-product-card__body">
            <span class="storefront-product-card__name">{{ $product->name }}</span>
            <span class="storefront-product-card__price">
                {{ number_format($displayPrice / 100, 2) }} {{ $displayCurrency }}
            </span>
            <span class="storefront-product-card__stock">
                {{ $product->inStock ? __('storefront::storefront.in_stock') : __('storefront::storefront.out_of_stock') }}
            </span>
        </span>
    </a>
    @if ($hookHtml !== '')
        <div class="storefront-product-card__hook">{!! $hookHtml !!}</div>
    @endif
    @if ($quickAdd && $product->inStock)
        <form method="POST" action="{{ route('storefront.cart.items.store') }}" class="storefront-product-card__actions">
            @csrf
            <input type="hidden" name="purchasable_uuid" value="{{ $product->variantUuid }}">
            <input type="hidden" name="quantity" value="1">
            <button type="submit" class="storefront-product-card__add">
                {{ __('storefront::storefront.add_to_cart') }}
            </button>
        </form>
    @endif
</article>
