@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.cart_title'))

@section('content')
    <div
        class="storefront-cart"
        data-cart
        data-currency="{{ $cart->currency }}"
        data-currency-symbol="{{ \Commerce\Cart\Support\StorefrontMoney::meta((string) $cart->currency)['symbol'] }}"
        data-cheapest-shipping="{{ $page->cheapestShipping }}"
    >
        <header class="storefront-cart__header">
            <x-storefront.breadcrumb :items="[
                ['label' => __('storefront::storefront.shop'), 'url' => route('storefront.shop.index')],
                ['label' => __('storefront::storefront.cart_title')],
            ]" />

            <div class="storefront-cart__title-row">
                <h1 class="storefront-cart__title">{{ __('storefront::storefront.cart_title') }}</h1>

                @if ($cart->lines !== [])
                    <form method="POST" action="{{ route('storefront.cart.clear') }}" class="storefront-cart__clear">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="storefront-cart__clear-btn">{{ __('storefront::storefront.clear_cart') }}</button>
                    </form>
                @endif
            </div>
        </header>

        @session('status')
            <div class="cf-flash cf-flash--success storefront-cart__flash">{{ $value }}</div>
        @endsession

        @if ($errors->any())
            <div class="cf-flash cf-flash--danger storefront-cart__flash">{{ $errors->first() }}</div>
        @endif

        @if ($cart->lines === [])
            <x-storefront.empty-state
                :title="__('storefront::storefront.cart_empty_title')"
                :description="__('storefront::storefront.cart_empty_description')"
                class="storefront-cart__empty"
            >
                <x-admin.button :href="route('storefront.shop.index')" variant="primary">
                    {{ __('storefront::storefront.continue_shopping') }}
                </x-admin.button>
            </x-storefront.empty-state>
        @else
            <div class="storefront-cart__layout">
                <section class="storefront-cart__main" aria-label="{{ __('storefront::storefront.cart_items') }}">
                    <x-storefront.cart.cart-items :lines="$page->lines" :currency="$cart->currency" />
                </section>

                <x-storefront.cart.cart-summary :cart="$cart" :page="$page" />
            </div>

            <div class="storefront-cart__recommendations">
                <x-storefront.cart.recommendation-rail
                    :title="__('storefront::storefront.recommended_for_cart')"
                    :products="$page->completeOrderProducts"
                    :stock-levels="$page->completeOrderStockLevels"
                    :currency="$cart->currency"
                />

                <x-storefront.product-section
                    :title="__('storefront::storefront.recently_viewed')"
                    class="storefront-cart__recently-viewed"
                    data-recently-viewed-section
                    hidden
                >
                    <x-storefront.product-grid data-recently-viewed-grid></x-storefront.product-grid>
                </x-storefront.product-section>

                <x-storefront.cart.recommendation-rail
                    :title="__('storefront::storefront.you_may_also_like')"
                    :products="$page->youMayAlsoLikeProducts"
                    :stock-levels="$page->youMayAlsoLikeStockLevels"
                    :currency="$cart->currency"
                />
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    @vite('resources/js/storefront/cart.js')
@endpush
