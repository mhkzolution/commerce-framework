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
                <div class="storefront-table-wrap">
                    <table class="storefront-table">
                        <thead>
                            <tr>
                                <th>{{ __('storefront::storefront.product') }}</th>
                                <th>{{ __('storefront::storefront.price') }}</th>
                                <th>{{ __('storefront::storefront.qty') }}</th>
                                <th>{{ __('storefront::storefront.total') }}</th>
                                <th class="storefront-table__num">{{ __('storefront::storefront.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cart->lines as $line)
                                <tr>
                                    <td>
                                        <div class="storefront-cart-line">
                                            @if ($line->imageUrl)
                                                <img src="{{ $line->imageUrl }}" alt="" class="storefront-cart-line__image">
                                            @endif
                                            <div>
                                                <div>{{ $line->name }}</div>
                                                <div class="storefront-muted">{{ $line->sku ?? $line->purchasableUuid }}</div>
                                                @if ($line->available < $line->quantity)
                                                    <div class="storefront-danger">{{ __('storefront::storefront.only_n_available', ['count' => $line->available]) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ number_format($line->unitPrice / 100, 2) }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('storefront.cart.items.update', $line->purchasableUuid) }}" class="storefront-qty-form">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" name="quantity" value="{{ $line->quantity }}" min="0" class="storefront-input storefront-input--qty">
                                            <button type="submit" class="storefront-btn storefront-btn--ghost">{{ __('storefront::storefront.update') }}</button>
                                        </form>
                                    </td>
                                    <td>{{ number_format($line->lineTotal / 100, 2) }}</td>
                                    <td class="storefront-table__num">
                                        <form method="POST" action="{{ route('storefront.cart.items.destroy', $line->purchasableUuid) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="storefront-btn storefront-btn--danger">{{ __('storefront::storefront.remove') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <aside class="storefront-cart__aside">
                    <div class="storefront-panel storefront-cart__summary">
                        <div>
                            <div class="storefront-muted">{{ trans_choice('storefront::storefront.subtotal_items', $cart->itemCount, ['count' => $cart->itemCount]) }}</div>
                            <p class="storefront-cart__total">{{ number_format($cart->subtotal / 100, 2) }} {{ $cart->currency }}</p>
                            @if ($cart->discountTotal > 0)
                                <div class="storefront-success">
                                    {{ $cart->promotionName }} ({{ $cart->couponCode }}): -{{ number_format($cart->discountTotal / 100, 2) }}
                                </div>
                            @endif
                        </div>
                        <a href="{{ route('storefront.checkout') }}" class="storefront-btn">{{ __('storefront::storefront.checkout') }}</a>
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
