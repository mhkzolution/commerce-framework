@props([
    'line',
    'currency',
])

@php
    /** @var \Commerce\Cart\DTO\StorefrontCartLineView $line */
    $resolved = $line->line;
    $productUrl = $line->productSlug !== ''
        ? route('storefront.products.show', $line->productSlug)
        : null;
@endphp

<article
    {{ $attributes->merge(['class' => 'storefront-cart-line']) }}
    data-cart-line
    data-purchasable-uuid="{{ $resolved->purchasableUuid }}"
    data-unit-price="{{ $resolved->unitPrice }}"
    data-quantity="{{ $resolved->quantity }}"
    data-line-total="{{ $resolved->lineTotal }}"
    @if ($line->productUuid) data-product-uuid="{{ $line->productUuid }}" @endif
>
    <label class="storefront-cart-line__select">
        <input
            type="checkbox"
            class="storefront-cart-line__checkbox"
            name="items[]"
            value="{{ $resolved->purchasableUuid }}"
            data-cart-line-select
            checked
        >
        <span class="sr-only">{{ __('storefront::storefront.select_item') }}</span>
    </label>

    <div class="storefront-cart-line__media">
        @if ($productUrl)
            <a href="{{ $productUrl }}" class="storefront-cart-line__media-link" aria-hidden="true" tabindex="-1">
                @if ($line->imageUrl)
                    <img src="{{ $line->imageUrl }}" alt="" class="storefront-cart-line__image" loading="lazy" decoding="async">
                @else
                    <div class="storefront-cart-line__placeholder">{{ __('storefront::storefront.no_image') }}</div>
                @endif
            </a>
        @elseif ($line->imageUrl)
            <img src="{{ $line->imageUrl }}" alt="" class="storefront-cart-line__image" loading="lazy" decoding="async">
        @else
            <div class="storefront-cart-line__placeholder">{{ __('storefront::storefront.no_image') }}</div>
        @endif
    </div>

    <div class="storefront-cart-line__content">
        <h2 class="storefront-cart-line__title" title="{{ $resolved->name }}">
            @if ($productUrl)
                <a href="{{ $productUrl }}">{{ $resolved->name }}</a>
            @else
                {{ $resolved->name }}
            @endif
        </h2>

        @if ($line->variantLabel && $productUrl)
            <a href="{{ $productUrl }}" class="storefront-cart-line__variant-btn">
                <span class="storefront-cart-line__variant-text">
                    {{ __('storefront::storefront.variant_options') }}: {{ $line->variantLabel }}
                </span>
                <span class="storefront-cart-line__variant-chevron" aria-hidden="true">›</span>
            </a>
        @elseif ($line->variantLabel)
            <p class="storefront-cart-line__variant-text storefront-cart-line__variant-text--static">
                {{ __('storefront::storefront.variant_options') }}: {{ $line->variantLabel }}
            </p>
        @endif

        @if ($line->deliverySummary || $resolved->available < $resolved->quantity)
            <div class="storefront-cart-line__badges">
                @if ($line->deliverySummary)
                    <span class="storefront-cart-line__badge storefront-cart-line__badge--shipping">{{ $line->deliverySummary }}</span>
                @endif
                @if ($resolved->available < $resolved->quantity)
                    <span class="storefront-cart-line__badge storefront-cart-line__badge--warning">
                        {{ __('storefront::storefront.only_available', ['count' => $resolved->available]) }}
                    </span>
                @endif
            </div>
        @endif

        <div class="storefront-cart-line__footer">
            <div class="storefront-cart-line__price">
                <x-storefront.price :amount="$resolved->unitPrice" :currency="$currency" minor />
            </div>

            <div class="storefront-cart-line__qty">
                <x-storefront.cart.quantity-stepper
                    :purchasable-uuid="$resolved->purchasableUuid"
                    :quantity="$resolved->quantity"
                    :max="$resolved->available > 0 ? $resolved->available : null"
                />
            </div>
        </div>
    </div>
</article>
