@props([
    'productUuid',
    'variantUuid' => null,
    'showLabel' => false,
])

<button
    type="button"
    {{ $attributes->class([
        'storefront-wishlist-btn',
        'storefront-wishlist-btn--labeled' => $showLabel,
    ]) }}
    data-wishlist-toggle
    data-product-uuid="{{ $productUuid }}"
    @if ($variantUuid) data-variant-uuid="{{ $variantUuid }}" @endif
    aria-label="{{ __('storefront::storefront.add_to_wishlist') }}"
    aria-pressed="false"
>
    <svg class="storefront-wishlist-btn__icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
    </svg>
    @if ($showLabel)
        <span class="storefront-wishlist-btn__label">{{ __('storefront::storefront.wishlist') }}</span>
    @endif
</button>
