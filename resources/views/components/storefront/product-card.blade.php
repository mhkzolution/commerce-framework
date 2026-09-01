@props([
    'product',
    'variant',
    'displayCurrency',
    'baseCurrency',
    'currencyConverter' => null,
    'available' => null,
])
<x-storefront.cards.product-card
    :product="$product"
    :variant="$variant"
    :display-currency="$displayCurrency"
    :base-currency="$baseCurrency"
    :currency-converter="$currencyConverter"
    :available="$available"
    {{ $attributes }}
/>
