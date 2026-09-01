@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.wishlist'))

@section('content')
    <div class="storefront-cart storefront-wishlist" data-wishlist-page>
        <header class="storefront-cart__header">
            <x-storefront.breadcrumb :items="[
                ['label' => __('storefront::storefront.shop'), 'url' => route('storefront.shop.index')],
                ['label' => __('storefront::storefront.wishlist')],
            ]" />

            <div class="storefront-cart__title-row">
                <h1 class="storefront-cart__title">{{ __('storefront::storefront.wishlist') }}</h1>
            </div>
        </header>

        <p class="storefront-wishlist-page__loading" data-wishlist-page-loading>{{ __('storefront::storefront.loading') }}</p>

        <x-storefront.empty-state
            :title="__('storefront::storefront.wishlist_empty')"
            :description="__('storefront::storefront.wishlist_empty_description')"
            class="storefront-cart__empty"
            data-wishlist-page-empty
            hidden
        >
            <x-storefront.buttons.primary-button :href="route('storefront.shop.index')">
                {{ __('storefront::storefront.continue_shopping') }}
            </x-storefront.buttons.primary-button>
        </x-storefront.empty-state>

        <section
            class="storefront-cart__main"
            data-wishlist-page-content
            hidden
            aria-label="{{ __('storefront::storefront.wishlist') }}"
        >
            <div class="storefront-cart-items">
                <div
                    class="storefront-cart-items__list"
                    data-wishlist-page-list
                    data-variant-options-label="{{ __('storefront::storefront.variant_options') }}"
                    data-add-to-cart-label="{{ __('storefront::storefront.add_to_cart') }}"
                    data-remove-label="{{ __('storefront::storefront.remove') }}"
                    data-no-image-label="{{ __('storefront::storefront.no_image') }}"
                    data-cart-store-url="{{ route('storefront.cart.items.store') }}"
                ></div>
            </div>
        </section>
    </div>
@endsection
