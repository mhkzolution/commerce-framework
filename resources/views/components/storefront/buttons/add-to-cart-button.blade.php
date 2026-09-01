@props([
    'purchasableUuid',
    'quantity' => 1,
    'label' => null,
    'compact' => false,
])

@php
    $label ??= __('storefront::storefront.add_to_cart');
@endphp

<form method="POST" action="{{ route('storefront.cart.items.store') }}" {{ $attributes->merge(['class' => 'storefront-add-to-cart' . ($compact ? ' storefront-add-to-cart--compact' : '')]) }}>
    @csrf
    <input type="hidden" name="purchasable_uuid" value="{{ $purchasableUuid }}">
    <input type="hidden" name="quantity" value="{{ $quantity }}">

    @if ($compact)
        <button type="submit" class="storefront-product-card__quick-add-btn" aria-label="{{ $label }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path d="M6 6h15l-1.5 9h-12z" />
                <circle cx="9" cy="20" r="1" />
                <circle cx="18" cy="20" r="1" />
                <path d="M6 6 5 3H2" />
            </svg>
        </button>
    @else
        <x-storefront.buttons.primary-button type="submit" class="w-full">{{ $label }}</x-storefront.buttons.primary-button>
    @endif
</form>
