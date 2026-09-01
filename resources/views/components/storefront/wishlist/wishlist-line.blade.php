@props([
    'item',
])

@php
    $variantLabel = $item['variant_label'] ?? null;
    $variantId = $item['variant_id'] ?? null;
@endphp

<article
    {{ $attributes->merge(['class' => 'storefront-cart-line storefront-cart-line--wishlist']) }}
    data-wishlist-page-item
    data-product-uuid="{{ $item['product_id'] }}"
>
    <div class="storefront-cart-line__select storefront-cart-line__select--spacer" aria-hidden="true">
        <span class="storefront-cart-line__checkbox-spacer"></span>
    </div>

    <div class="storefront-cart-line__media">
        <a href="{{ $item['url'] }}" class="storefront-cart-line__media-link" aria-hidden="true" tabindex="-1">
            @if (! empty($item['image_url']))
                <img src="{{ $item['image_url'] }}" alt="" class="storefront-cart-line__image" loading="lazy" decoding="async">
            @else
                <div class="storefront-cart-line__placeholder">{{ __('storefront::storefront.no_image') }}</div>
            @endif
        </a>
    </div>

    <div class="storefront-cart-line__content">
        <h2 class="storefront-cart-line__title" title="{{ $item['name'] }}">
            <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
        </h2>

        @if ($variantLabel)
            <a href="{{ $item['url'] }}" class="storefront-cart-line__variant-btn">
                <span class="storefront-cart-line__variant-text">
                    {{ __('storefront::storefront.variant_options') }}: {{ $variantLabel }}
                </span>
                <span class="storefront-cart-line__variant-chevron" aria-hidden="true">›</span>
            </a>
        @endif

        <div class="storefront-cart-line__footer">
            <div class="storefront-cart-line__price">
                <x-storefront.commerce.price :amount="$item['price']" :currency="$item['currency']" />
            </div>

            <div class="storefront-cart-line__qty storefront-wishlist-line__actions">
                @if ($variantId)
                    <form method="POST" action="{{ route('storefront.cart.items.store') }}" class="storefront-wishlist-line__add-form">
                        @csrf
                        <input type="hidden" name="purchasable_uuid" value="{{ $variantId }}">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="storefront-wishlist-line__add-btn">
                            {{ __('storefront::storefront.add_to_cart') }}
                        </button>
                    </form>
                @endif

                <button
                    type="button"
                    class="storefront-wishlist-line__remove-btn"
                    data-wishlist-page-remove
                    data-product-uuid="{{ $item['product_id'] }}"
                    @if ($variantId) data-variant-uuid="{{ $variantId }}" @endif
                >
                    {{ __('storefront::storefront.remove') }}
                </button>
            </div>
        </div>
    </div>
</article>
