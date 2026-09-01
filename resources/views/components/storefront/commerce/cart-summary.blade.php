@props([
    'cart',
    'page',
])

<x-storefront.cart.cart-summary :cart="$cart" :page="$page" {{ $attributes }} />
