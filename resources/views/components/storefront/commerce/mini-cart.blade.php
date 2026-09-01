@props([
    'itemCount' => 0,
])

<a href="{{ route('storefront.cart.index') }}" {{ $attributes->merge(['class' => 'storefront-mini-cart']) }} aria-label="{{ __('storefront::storefront.cart') }}">
    <svg class="storefront-mini-cart__icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
        <path d="M6 6h15l-1.5 9h-12z" />
        <circle cx="9" cy="20" r="1" />
        <circle cx="18" cy="20" r="1" />
        <path d="M6 6 5 3H2" />
    </svg>
    @if ($itemCount > 0)
        <span class="storefront-mini-cart__count" data-mini-cart-count>{{ $itemCount }}</span>
    @endif
</a>
