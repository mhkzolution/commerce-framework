@props([
    'methods' => null,
])

@php
    $methods ??= config('cart.storefront.payment_methods', []);
@endphp

@if ($methods !== [])
    <div {{ $attributes->merge(['class' => 'storefront-payment-icons']) }} aria-label="{{ __('storefront::storefront.payment_methods') }}">
        @foreach ($methods as $method)
            <span class="storefront-payment-icons__item storefront-payment-icons__item--{{ $method }}" title="{{ strtoupper(str_replace('_', ' ', $method)) }}">
                <span class="sr-only">{{ strtoupper(str_replace('_', ' ', $method)) }}</span>
            </span>
        @endforeach
    </div>
@endif
