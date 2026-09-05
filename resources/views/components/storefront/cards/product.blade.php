@props([
    'product',
    'displayCurrency' => '',
    'baseCurrency' => null,
    'currencyConverter' => null,
    'quickAdd' => false,
    'priority' => false,
])

@php
    if (! $product instanceof \Commerce\Contracts\Storefront\ProductCardData) {
        throw new \InvalidArgumentException('x-storefront.cards.product requires ProductCardData.');
    }

    $displayPrice = $product->price;
    $displayCompare = $product->compareAtPrice;
    if ($currencyConverter && $baseCurrency && $displayCurrency && $displayCurrency !== $baseCurrency) {
        $displayPrice = $currencyConverter->convert($displayPrice, $baseCurrency, $displayCurrency);
        if ($displayCompare !== null) {
            $displayCompare = $currencyConverter->convert($displayCompare, $baseCurrency, $displayCurrency);
        }
    }

    $hookHtml = '';
    if (app()->bound(\Commerce\Contracts\Hook\HookRegistryInterface::class)) {
        $hookHtml = app(\Commerce\Contracts\Hook\HookRegistryInterface::class)->filter(
            'storefront.product.card',
            '',
            ['product' => $product],
        );
    }
@endphp

<article
    {{ $attributes->merge(['class' => 'storefront-product-card']) }}
    data-product-card
    data-product-uuid="{{ $product->uuid }}"
>
    <div class="storefront-product-card__media">
        <a href="{{ $product->url }}" class="storefront-product-card__media-link" aria-label="{{ $product->name }}">
            @if ($hookHtml !== '')
                <div class="storefront-product-card__badges">{!! $hookHtml !!}</div>
            @endif

            @if ($product->imageUrl)
                <x-storefront.media.img
                    :src="$product->imageUrl"
                    :srcset="$product->imageSrcset"
                    :sizes="config('media.sizes.card')"
                    :alt="$product->name"
                    class="storefront-product-card__image storefront-product-card__image--primary"
                    :loading="$priority ? false : 'lazy'"
                    :fetchpriority="$priority ? 'high' : null"
                />
                @if ($product->secondaryImageUrl)
                    <x-storefront.media.img
                        :src="$product->secondaryImageUrl"
                        :srcset="$product->secondaryImageSrcset"
                        :sizes="config('media.sizes.card')"
                        alt=""
                        class="storefront-product-card__image storefront-product-card__image--secondary"
                        loading="lazy"
                        aria-hidden="true"
                    />
                @endif
            @else
                <div class="storefront-product-card__placeholder">{{ __('storefront::storefront.no_image') }}</div>
            @endif
        </a>

        <div class="storefront-product-card__quick-actions">
            <button
                type="button"
                class="storefront-quick-view-btn"
                data-quick-view-open="{{ $product->uuid }}"
                aria-label="{{ __('storefront::storefront.quick_view') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
            </button>

            <x-storefront.buttons.wishlist-button
                :product-uuid="$product->uuid"
                :variant-uuid="$product->variantUuid"
            />

            @if ($quickAdd && $product->inStock)
                <form method="POST" action="{{ route('storefront.cart.items.store') }}" class="storefront-product-card__quick-add">
                    @csrf
                    <input type="hidden" name="purchasable_uuid" value="{{ $product->variantUuid }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="storefront-quick-view-btn" aria-label="{{ __('storefront::storefront.add_to_cart') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <circle cx="9" cy="21" r="1" />
                            <circle cx="20" cy="21" r="1" />
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="storefront-product-card__body">
        <a href="{{ $product->url }}" class="storefront-product-card__name">{{ $product->name }}</a>

        <div class="storefront-product-card__meta">
            <span class="storefront-product-card__price">
                {{ number_format($displayPrice / 100, 2) }} {{ $displayCurrency }}
            </span>
            @if ($displayCompare !== null && $displayCompare > $displayPrice)
                <span class="storefront-product-card__compare">
                    {{ number_format($displayCompare / 100, 2) }} {{ $displayCurrency }}
                </span>
            @endif
            <span class="storefront-product-card__stock">
                {{ $product->inStock ? __('storefront::storefront.in_stock') : __('storefront::storefront.out_of_stock') }}
            </span>
        </div>
    </div>
</article>
