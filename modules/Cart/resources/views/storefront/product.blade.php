@extends('cart::layouts.storefront')

@section('title', $product->name)

@section('content')
    @php
        $breadcrumbItems = [
            ['label' => __('storefront::storefront.shop'), 'url' => route('storefront.shop.index')],
        ];

        if ($product->categories->isNotEmpty()) {
            $category = $product->categories->first();
            $breadcrumbItems[] = [
                'label' => $category->name,
                'url' => route('storefront.shop.index', ['category' => $category->slug]),
            ];
        }

        $breadcrumbItems[] = ['label' => $product->name];
    @endphp

    <article
        class="storefront-pdp storefront-pdp--market"
        data-product-page
        data-product-uuid="{{ $product->uuid }}"
        data-product-slug="{{ $product->slug }}"
        data-product-name="{{ $product->name }}"
        data-product-image="{{ $galleryItems[0]['thumbnail'] ?? $galleryItems[0]['url'] ?? '' }}"
        data-product-price="{{ $variant?->price ?? 0 }}"
        data-product-currency="{{ $displayCurrency }}"
        data-product-currency-symbol="{{ \Commerce\Cart\Support\StorefrontMoney::meta((string) $displayCurrency)['symbol'] }}"
        data-variants='@json($variantPayload)'
        data-variant-axes='@json($variantOptionAxes)'
    >
        <x-storefront.breadcrumb :items="$breadcrumbItems" />

        <div class="storefront-pdp__panels">
            <section class="storefront-pdp__panel storefront-pdp__panel--gallery" aria-label="{{ __('storefront::storefront.product_gallery') }}">
                <div class="storefront-pdp__panel-media">
                    <x-storefront.commerce.product-gallery :items="$galleryItems" data-product-gallery-root />
                </div>

                <div class="storefront-pdp__gallery-footer">
                    <div class="storefront-pdp__share">
                        <span class="storefront-pdp__share-label">{{ __('storefront::storefront.share') }}:</span>
                        <x-storefront.share-button :url="url()->current()" :title="$product->name" class="storefront-pdp__share-btn" />
                    </div>
                    <x-storefront.wishlist-button
                        :product-uuid="$product->uuid"
                        :variant-uuid="$variant?->uuid"
                        class="storefront-pdp__favorite"
                        :show-label="true"
                    />
                </div>
            </section>

            <section class="storefront-pdp__panel storefront-pdp__panel--buy">
                <x-storefront.product-buy-box
                    :product="$product"
                    :variant="$variant"
                    :variants="$variantPayload"
                    :variant-option-axes="$variantOptionAxes"
                    :price-summary="$priceSummary"
                    :available="$available"
                    :display-currency="$displayCurrency"
                    :base-currency="$baseCurrency"
                    :currency-converter="$currencyConverter"
                    :visible-attributes="$visibleAttributes"
                    :delivery-summary="$deliverySummary"
                    :gallery-items="$galleryItems"
                />
            </section>
        </div>

        <x-storefront.pdp-detail-sections
            :product="$product"
            :visible-attributes="$visibleAttributes"
            class="storefront-pdp__details"
        />

        <x-storefront.product-section
            :title="__('storefront::storefront.recently_viewed')"
            data-recently-viewed-section
            hidden
            class="storefront-pdp__product-section"
        >
            <x-storefront.product-grid class="storefront-product-grid--pdp" data-recently-viewed-grid></x-storefront.product-grid>
            <div class="storefront-pdp-pagination" data-pdp-pagination="recently-viewed" hidden>
                <p class="storefront-pdp-pagination__infinite" data-pdp-infinite-loading aria-live="polite">
                    {{ __('storefront::storefront.scroll_to_load') }}
                </p>
                <div data-pdp-load-sentinel></div>
            </div>
        </x-storefront.product-section>

        @if ($recommendedProducts->isNotEmpty())
            <x-storefront.product-section
                :title="__('storefront::storefront.related_products')"
                class="storefront-pdp__product-section"
            >
                <x-storefront.product-grid class="storefront-product-grid--pdp" data-recommended-grid>
                    @foreach ($recommendedProducts as $index => $related)
                        @php $relatedVariant = $related->defaultVariant(); @endphp
                        @if ($relatedVariant)
                            <div
                                class="storefront-pdp-card"
                                data-pdp-card
                                data-pdp-index="{{ $index }}"
                                @if ($index >= 6) hidden @endif
                            >
                                <x-storefront.product-card
                                    :product="$related"
                                    :variant="$relatedVariant"
                                    :display-currency="$displayCurrency"
                                    :base-currency="$baseCurrency"
                                    :currency-converter="$currencyConverter"
                                    :available="$sectionStockLevels[$relatedVariant->uuid] ?? null"
                                />
                            </div>
                        @endif
                    @endforeach
                </x-storefront.product-grid>

                @if ($recommendedProducts->count() > 6)
                    <div
                        class="storefront-pdp-pagination"
                        data-pdp-pagination="recommended"
                        data-pdp-batch-size="6"
                        data-pdp-visible="6"
                        data-pdp-total="{{ $recommendedProducts->count() }}"
                    >
                        <button type="button" class="storefront-pdp-pagination__load-more" data-pdp-load-more>
                            {{ __('storefront::storefront.load_more') }}
                        </button>
                        <p class="storefront-pdp-pagination__infinite" data-pdp-infinite-loading aria-live="polite">
                            {{ __('storefront::storefront.scroll_to_load') }}
                        </p>
                        <div data-pdp-load-sentinel></div>
                    </div>
                @endif
            </x-storefront.product-section>
        @endif

        @if ($variant && $available > 0)
            <div class="storefront-mobile-buy-bar storefront-mobile-buy-bar--market" data-mobile-buy-bar>
                <div class="storefront-mobile-buy-bar__price" data-mobile-buy-price>
                    {{ \Commerce\Cart\Support\StorefrontMoney::formatMajor(
                        (float) ($currencyConverter && $displayCurrency !== $baseCurrency ? $currencyConverter->convert($variant->price, $baseCurrency, $displayCurrency) : $variant->price),
                        (string) $displayCurrency,
                        0,
                    ) }}
                </div>
                <button type="button" class="storefront-mobile-buy-bar__button storefront-mobile-buy-bar__button--cart" data-mobile-buy-trigger="cart">
                    {{ __('storefront::storefront.add_to_cart') }}
                </button>
                <button type="button" class="storefront-mobile-buy-bar__button storefront-mobile-buy-bar__button--buy" data-mobile-buy-trigger="checkout">
                    {{ __('storefront::storefront.buy_now') }}
                </button>
            </div>
        @endif
    </article>
@endsection

@push('scripts')
    @vite('resources/js/storefront/product.js')
@endpush
