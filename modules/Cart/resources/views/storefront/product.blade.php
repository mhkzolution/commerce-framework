@extends('cart::layouts.storefront')

@section('title', $product->name)
@section('main_class', 'storefront-pdp-main')

@push('head')
    @vite(['resources/css/storefront/pdp.css', 'resources/js/storefront/product.js'])
@endpush

@php
    if (! $product instanceof \Commerce\Contracts\Storefront\ProductDetailData) {
        throw new \InvalidArgumentException('PDP requires ProductDetailData.');
    }

    $formatMoney = static fn (int $amount): string => number_format($amount / 100, 2).' '.$product->displayCurrency;
    $galleryImage = $product->gallery[0]['thumbnail'] ?? $product->gallery[0]['url'] ?? $product->imageUrl ?? '';
@endphp

@section('content')
    <x-storefront.layout.page-container class="storefront-pdp">
        <article
            class="storefront-pdp storefront-pdp--market"
            data-product-page
            data-product-uuid="{{ $product->uuid }}"
            data-product-slug="{{ $product->slug }}"
            data-product-name="{{ $product->name }}"
            data-product-image="{{ $galleryImage }}"
            data-product-price="{{ $product->price }}"
            data-product-currency="{{ $product->displayCurrency }}"
            data-variants='@json($product->variants)'
            data-variant-axes='@json($product->variantAxes)'
        >
            <x-storefront.breadcrumb
                :aria-label="__('storefront::storefront.breadcrumb')"
                :items="$product->breadcrumbItems !== [] ? $product->breadcrumbItems : [
                    ['label' => __('storefront::storefront.shop'), 'url' => $product->shopUrl],
                    ['label' => $product->name],
                ]"
            />

            <div class="storefront-pdp__panels">
                <section class="storefront-pdp__panel storefront-pdp__panel--gallery" aria-label="{{ __('storefront::storefront.product_gallery') }}">
                    <div class="storefront-pdp__panel-media">
                        <x-storefront.commerce.product-gallery :items="$product->gallery" data-product-gallery-root />
                    </div>

                    <div class="storefront-pdp__gallery-footer">
                        <div class="storefront-pdp__share">
                            <span class="storefront-pdp__share-label">{{ __('storefront::storefront.share') }}:</span>
                            <x-storefront.buttons.share-button :url="url()->current()" :title="$product->name" class="storefront-pdp__share-btn" />
                        </div>
                        <x-storefront.buttons.wishlist-button
                            :product-uuid="$product->uuid"
                            :variant-uuid="$product->variantUuid"
                            class="storefront-pdp__favorite"
                            :show-label="true"
                        />
                    </div>
                </section>

                <section class="storefront-pdp__panel storefront-pdp__panel--buy">
                    <aside class="storefront-buy-box storefront-buy-box--market" data-buy-box>
                        <h1 class="storefront-buy-box__title">{{ $product->name }}</h1>

                        <div class="storefront-buy-box__price-panel" data-buy-price-panel>
                            <div class="storefront-buy-box__price" data-buy-price>
                                <span class="storefront-buy-box__amount" data-buy-amount>{{ $formatMoney($product->price) }}</span>
                                @if ($product->compareAtPrice && $product->compareAtPrice > $product->price)
                                    <span class="storefront-buy-box__compare" data-buy-compare>{{ $formatMoney($product->compareAtPrice) }}</span>
                                @endif
                                @if ($product->discountPercent)
                                    <span class="storefront-buy-box__discount" data-buy-discount>-{{ $product->discountPercent }}%</span>
                                @endif
                            </div>
                        </div>

                        <x-storefront.forms.variant-axis-selector
                            :axes="$product->variantAxes"
                            :variants="$product->variants"
                            :selected-uuid="$product->variantUuid"
                            class="storefront-buy-box__variants"
                        />

                        @if ($product->inStock)
                            <form method="POST" action="{{ route('storefront.cart.items.store') }}" class="storefront-buy-box__form" data-buy-form>
                                @csrf
                                <input type="hidden" name="purchasable_uuid" value="{{ $product->variantUuid }}" data-buy-variant-input>

                                <div class="storefront-buy-box__quantity-row">
                                    <span class="storefront-buy-box__quantity-label">{{ __('storefront::storefront.quantity') }}</span>
                                    <div class="storefront-qty-stepper" data-qty-stepper>
                                        <button type="button" class="storefront-qty-stepper__btn" data-qty-decrease aria-label="{{ __('storefront::storefront.decrease_quantity') }}">−</button>
                                        <input
                                            type="number"
                                            name="quantity"
                                            value="1"
                                            min="1"
                                            @if ($product->available !== null) max="{{ $product->available }}" @endif
                                            class="storefront-qty-stepper__input"
                                            data-buy-quantity
                                            aria-label="{{ __('storefront::storefront.quantity') }}"
                                        >
                                        <button type="button" class="storefront-qty-stepper__btn" data-qty-increase aria-label="{{ __('storefront::storefront.increase_quantity') }}">+</button>
                                    </div>
                                    <span
                                        class="storefront-buy-box__stock-note"
                                        data-buy-stock-note
                                        data-in-stock-label="{{ __('storefront::storefront.in_stock') }}"
                                        data-out-of-stock-label="{{ __('storefront::storefront.out_of_stock') }}"
                                    >{{ __('storefront::storefront.in_stock') }}</span>
                                    @if ($product->sku)
                                        <span class="storefront-buy-box__sku">{{ __('storefront::storefront.sku') }} {{ $product->sku }}</span>
                                    @endif
                                </div>

                                <div class="storefront-buy-box__actions-row">
                                    <button type="submit" class="storefront-buy-box__cta storefront-buy-box__cta--cart storefront-pdp__add">
                                        {{ __('storefront::storefront.add_to_cart') }}
                                    </button>
                                    <button type="submit" class="storefront-buy-box__cta storefront-buy-box__cta--buy" name="redirect_to" value="checkout">
                                        {{ __('storefront::storefront.buy_now') }}
                                    </button>
                                </div>
                            </form>
                        @else
                            <p class="storefront-buy-box__unavailable">{{ __('storefront::storefront.out_of_stock') }}</p>
                        @endif
                    </aside>
                </section>
            </div>

            @if ($product->attributes !== [] || $product->description)
                <div class="storefront-pdp-details">
                    @if ($product->attributes !== [])
                        <section class="storefront-pdp-details__section storefront-pdp__panel" aria-labelledby="pdp-specs-heading">
                            <h2 id="pdp-specs-heading" class="storefront-pdp-details__heading">
                                {{ __('storefront::storefront.section_specifications') }}
                            </h2>
                            <dl class="storefront-pdp-spec-list">
                                @foreach ($product->attributes as $attribute)
                                    <div class="storefront-pdp-spec-list__row">
                                        <dt class="storefront-pdp-spec-list__label">{{ $attribute['label'] }}</dt>
                                        <dd class="storefront-pdp-spec-list__value">{{ $attribute['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endif

                    @if ($product->description)
                        <section class="storefront-pdp-details__section storefront-pdp__panel" aria-labelledby="pdp-description-heading">
                            <h2 id="pdp-description-heading" class="storefront-pdp-details__heading">
                                {{ __('storefront::storefront.section_description') }}
                            </h2>
                            <div class="storefront-pdp-details__prose">{!! nl2br(e($product->description)) !!}</div>
                        </section>
                    @endif
                </div>
            @endif

            <section
                class="storefront-pdp__product-section"
                data-recently-viewed-section
                hidden
                aria-labelledby="pdp-recent-heading"
            >
                <h2 id="pdp-recent-heading" class="storefront-product-section__title">{{ __('storefront::storefront.recently_viewed') }}</h2>
                <div class="storefront-product-grid storefront-product-grid--pdp" data-recently-viewed-grid></div>
                <div class="storefront-pdp-pagination" data-pdp-pagination="recently-viewed" hidden>
                    <p class="storefront-pdp-pagination__infinite" data-pdp-infinite-loading aria-live="polite">
                        {{ __('storefront::storefront.scroll_to_load') }}
                    </p>
                    <div data-pdp-load-sentinel></div>
                </div>
            </section>

            @if ($product->relatedProducts !== [])
                <section class="storefront-pdp__product-section" aria-labelledby="pdp-related-heading">
                    <h2 id="pdp-related-heading" class="storefront-product-section__title">{{ __('storefront::storefront.related_products') }}</h2>
                    <div class="storefront-product-grid storefront-product-grid--pdp" data-recommended-grid>
                        @foreach ($product->relatedProducts as $index => $related)
                            <div
                                class="storefront-pdp-card"
                                data-pdp-card
                                data-pdp-index="{{ $index }}"
                                @if ($index >= 6) hidden @endif
                            >
                                <x-storefront.cards.product
                                    :product="$related"
                                    :display-currency="$product->displayCurrency"
                                />
                            </div>
                        @endforeach
                    </div>

                    @if (count($product->relatedProducts) > 6)
                        <div
                            class="storefront-pdp-pagination"
                            data-pdp-pagination="recommended"
                            data-pdp-batch-size="6"
                            data-pdp-visible="6"
                            data-pdp-total="{{ count($product->relatedProducts) }}"
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
                </section>
            @endif

            @if ($product->inStock)
                <div class="storefront-mobile-buy-bar storefront-mobile-buy-bar--market" data-mobile-buy-bar>
                    <div class="storefront-mobile-buy-bar__price" data-mobile-buy-price>{{ $formatMoney($product->price) }}</div>
                    <button type="button" class="storefront-mobile-buy-bar__button storefront-mobile-buy-bar__button--cart" data-mobile-buy-trigger="cart">
                        {{ __('storefront::storefront.add_to_cart') }}
                    </button>
                    <button type="button" class="storefront-mobile-buy-bar__button storefront-mobile-buy-bar__button--buy" data-mobile-buy-trigger="checkout">
                        {{ __('storefront::storefront.buy_now') }}
                    </button>
                </div>
            @endif
        </article>
    </x-storefront.layout.page-container>
@endsection
