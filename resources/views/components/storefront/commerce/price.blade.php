@props([
    'amount',
    'currency',
    'minor' => false,
])

@php
    use Commerce\Cart\Support\StorefrontMoney;

    $formatted = $minor
        ? StorefrontMoney::formatMinor((int) $amount, (string) $currency)
        : StorefrontMoney::formatMajor((float) $amount, (string) $currency);
@endphp

<span {{ $attributes->merge(['class' => 'storefront-price storefront-product-card__price']) }}>
    {{ $formatted }}
</span>
