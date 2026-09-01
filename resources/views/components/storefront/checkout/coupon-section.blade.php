@props([
    'cart',
])

<x-storefront.cart.coupon-form :cart="$cart" {{ $attributes }} />
