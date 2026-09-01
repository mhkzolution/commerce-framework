@props(['productUuid', 'variantUuid' => null, 'showLabel' => false])
<x-storefront.buttons.wishlist-button :product-uuid="$productUuid" :variant-uuid="$variantUuid" :show-label="$showLabel" {{ $attributes }} />
