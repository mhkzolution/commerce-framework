@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.cart'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    <x-storefront.layout.page-container class="storefront-shopper storefront-cart">
        <x-storefront.breadcrumb :items="[
            ['label' => __('storefront::storefront.shop'), 'url' => route('storefront.shop.index')],
            ['label' => __('storefront::storefront.cart')],
        ]" :aria-label="__('storefront::storefront.breadcrumb')" />

        <x-storefront.checkout.progress current="cart" />

        <div class="storefront-shopper__header">
            <h1 class="storefront-shopper__title">{{ __('storefront::storefront.cart') }}</h1>
            @if ($cart->lines !== [])
                <form method="POST" action="{{ route('storefront.cart.clear') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="storefront-btn storefront-btn--danger">{{ __('storefront::storefront.clear_cart') }}</button>
                </form>
            @endif
        </div>

        @session('status')
            <div class="storefront-flash storefront-flash--success">{{ $value }}</div>
        @endsession

        @if ($errors->any())
            <div class="storefront-flash storefront-flash--danger">{{ $errors->first() }}</div>
        @endif

        @if ($cart->lines === [])
            <x-storefront.empty-state :title="__('storefront::storefront.cart_empty')">
                <a href="{{ route('storefront.shop.index') }}" class="storefront-link">{{ __('storefront::storefront.continue_shopping') }}</a>
            </x-storefront.empty-state>
        @else
            <div class="storefront-cart__layout">
                <ul class="storefront-cart-lines">
                    @foreach ($cart->lines as $line)
                        <li class="storefront-cart-item">
                            @if ($line->imageUrl)
                                <x-storefront.media.img
                                    :src="$line->imageUrl"
                                    :srcset="$line->imageSrcset"
                                    :sizes="config('media.sizes.cart')"
                                    alt=""
                                    class="storefront-cart-item__image"
                                />
                            @else
                                <div class="storefront-cart-item__placeholder">{{ __('storefront::storefront.no_image') }}</div>
                            @endif
                            <div class="storefront-cart-item__details">
                                @if ($line->url)
                                    <a href="{{ $line->url }}" class="storefront-cart-item__name">{{ $line->productName ?? $line->name }}</a>
                                @else
                                    <div class="storefront-cart-item__name">{{ $line->productName ?? $line->name }}</div>
                                @endif
                                @if ($line->variantLabel)
                                    <p class="storefront-muted">{{ $line->variantLabel }}</p>
                                @endif
                                <p class="storefront-muted">{{ $line->sku }}</p>
                                @if ($line->available < $line->quantity)
                                    <p class="storefront-danger">{{ __('storefront::storefront.only_n_available', ['count' => $line->available]) }}</p>
                                @endif
                                <p class="storefront-cart-item__price">{{ number_format($line->unitPrice / 100, 2) }} {{ $cart->currency }}</p>
                            </div>
                            <div class="storefront-cart-item__controls">
                                <form method="POST" action="{{ route('storefront.cart.items.update', $line->purchasableUuid) }}" class="storefront-qty-stepper" data-qty-stepper data-cart-qty>
                                    @csrf
                                    @method('PATCH')
                                    <button type="button" class="storefront-qty-stepper__btn" data-qty-dec aria-label="{{ __('storefront::storefront.decrease_quantity') }}">−</button>
                                    <input type="number" name="quantity" value="{{ $line->quantity }}" min="0" max="{{ $line->available }}" class="storefront-qty-stepper__input">
                                    <button type="button" class="storefront-qty-stepper__btn" data-qty-inc aria-label="{{ __('storefront::storefront.increase_quantity') }}">+</button>
                                </form>
                                <p class="storefront-cart-item__total">{{ number_format($line->lineTotal / 100, 2) }}</p>
                                <form method="POST" action="{{ route('storefront.cart.items.destroy', $line->purchasableUuid) }}" data-cart-remove>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="storefront-btn storefront-btn--danger">{{ __('storefront::storefront.remove') }}</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <aside class="storefront-cart__aside">
                    <div class="storefront-panel storefront-cart__summary storefront-cart__summary--sticky">
                        <h2 class="storefront-panel__title">{{ __('storefront::storefront.order_summary') }}</h2>
                        <div>
                            <div class="storefront-muted">{{ trans_choice('storefront::storefront.subtotal_items', $cart->itemCount, ['count' => $cart->itemCount]) }}</div>
                            <p class="storefront-cart__total">{{ number_format($cart->subtotal / 100, 2) }} {{ $cart->currency }}</p>
                            @if ($cart->discountTotal > 0)
                                <div class="storefront-success">
                                    {{ $cart->promotionName }} ({{ $cart->couponCode }}): -{{ number_format($cart->discountTotal / 100, 2) }}
                                </div>
                            @endif
                        </div>
                        <a href="{{ route('storefront.checkout') }}" class="storefront-btn storefront-btn--block">{{ __('storefront::storefront.checkout') }}</a>
                    </div>

                    <section class="storefront-panel">
                        <h2 class="storefront-panel__title">{{ __('storefront::storefront.promotion_code') }}</h2>
                        @if ($cart->couponCode)
                            <div class="storefront-cart__coupon">
                                <span class="storefront-success">{{ __('storefront::storefront.coupon_applied', ['code' => $cart->couponCode]) }}</span>
                                <form method="POST" action="{{ route('storefront.cart.coupon.remove') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="storefront-btn storefront-btn--danger">{{ __('storefront::storefront.remove') }}</button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('storefront.cart.coupon.apply') }}" class="storefront-cart__coupon">
                                @csrf
                                <input name="code" placeholder="{{ __('storefront::storefront.enter_code') }}" class="storefront-input storefront-input--grow">
                                <button type="submit" class="storefront-btn storefront-btn--secondary">{{ __('storefront::storefront.apply') }}</button>
                            </form>
                        @endif
                        @error('coupon')<p class="storefront-danger">{{ $message }}</p>@enderror
                    </section>
                </aside>
            </div>
        @endif
    </x-storefront.layout.page-container>
@endsection
