@props([
    'product',
    'variant',
    'displayCurrency',
    'baseCurrency',
    'currencyConverter' => null,
    'available' => null,
    'priority' => false,
])

@inject('imageResolver', 'Commerce\Product\Services\ProductImageResolver')
@inject('customerExperience', 'Commerce\Settings\Services\CustomerExperienceConfig')

@php
    $images = $imageResolver->urlsForProduct($product, 'medium', 2);
    $primaryImage = $images[0] ?? null;
    $secondaryImage = $images[1] ?? null;
    $productUrl = route('storefront.products.show', $product->slug);
    $quickViewEnabled = $customerExperience->quickViewEnabled();

    $displayPrice = $variant->price;
    if ($currencyConverter && $displayCurrency !== $baseCurrency) {
        $displayPrice = $currencyConverter->convert($displayPrice, $baseCurrency, $displayCurrency);
    }

    $badges = app(\Commerce\Contracts\Hook\HookRegistryInterface::class)
        ->filter('storefront.product.card', '', ['product' => $product, 'variant' => $variant]);
@endphp

<article
    {{ $attributes->merge(['class' => 'storefront-product-card']) }}
    data-product-card
    data-product-id="{{ $product->uuid }}"
    data-product-uuid="{{ $product->uuid }}"
    data-product-name="{{ $product->name }}"
    data-product-price="{{ $displayPrice }}"
    data-product-thumbnail="{{ $primaryImage }}"
>
    <div class="storefront-product-card__media">
        <a href="{{ $productUrl }}" class="storefront-product-card__media-link" aria-label="{{ $product->name }}">
            @if ($badges)
                <div class="storefront-product-card__badges">{!! $badges !!}</div>
            @endif

            @if ($primaryImage)
                <img
                    src="{{ $primaryImage }}"
                    alt="{{ $product->name }}"
                    class="storefront-product-card__image storefront-product-card__image--primary"
                    @if ($priority) fetchpriority="high" @else loading="lazy" @endif
                    decoding="async"
                >
                @if ($secondaryImage)
                    <img
                        src="{{ $secondaryImage }}"
                        alt=""
                        class="storefront-product-card__image storefront-product-card__image--secondary"
                        loading="lazy"
                        decoding="async"
                        aria-hidden="true"
                    >
                @endif
            @else
                <div class="storefront-product-card__placeholder">{{ __('storefront::storefront.no_image') }}</div>
            @endif
        </a>

        <div class="storefront-product-card__quick-actions">
            @if ($quickViewEnabled)
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
            @endif
            <x-storefront.buttons.wishlist-button :product-uuid="$product->uuid" />
        </div>
    </div>

    <div class="storefront-product-card__body">
        <a href="{{ $productUrl }}" class="storefront-product-card__name">{{ $product->name }}</a>

        <div class="storefront-product-card__meta">
            <x-storefront.commerce.price :amount="$displayPrice" :currency="$displayCurrency" />
        </div>
    </div>
</article>
