@props([
    'cart',
    'estimatedTotal',
])

<x-storefront.cart.summary-totals :cart="$cart" :estimated-total="$estimatedTotal" {{ $attributes }}>
    {{ $slot }}
</x-storefront.cart.summary-totals>
