@extends('cart::layouts.storefront')

@section('title', $product->name)
@section('main_class', 'storefront-pdp-main')

@push('head')
    @vite('resources/css/storefront/pdp.css')
@endpush

@php
    if (! $product instanceof \Commerce\Contracts\Storefront\ProductDetailData) {
        throw new \InvalidArgumentException('PDP requires ProductDetailData.');
    }
@endphp

@section('content')
    <x-storefront.layout.page-container class="storefront-pdp">
        <x-storefront.breadcrumb
            :aria-label="__('storefront::storefront.breadcrumb')"
            :items="[
                ['label' => __('storefront::storefront.shop'), 'url' => $product->shopUrl],
                ['label' => $product->name],
            ]"
        />

        <div class="storefront-pdp__layout">
            <div class="storefront-pdp__media">
                @if ($product->imageUrl)
                    <img
                        src="{{ $product->imageUrl }}"
                        alt="{{ $product->name }}"
                        class="storefront-pdp__image"
                    >
                @else
                    <span class="storefront-pdp__placeholder"></span>
                @endif
            </div>

            <div class="storefront-pdp__info">
                <h1 class="storefront-pdp__title">{{ $product->name }}</h1>

                @if ($product->description)
                    <div class="storefront-pdp__description">{!! nl2br(e($product->description)) !!}</div>
                @endif

                <p class="storefront-pdp__price">
                    @if ($product->compareAtPrice)
                        <span class="storefront-pdp__compare">{{ number_format($product->compareAtPrice / 100, 2) }} {{ $product->displayCurrency }}</span>
                    @endif
                    <span class="storefront-pdp__amount">{{ number_format($product->price / 100, 2) }} {{ $product->displayCurrency }}</span>
                </p>

                <p class="storefront-pdp__meta">
                    <span>{{ $product->inStock ? __('storefront::storefront.in_stock') : __('storefront::storefront.out_of_stock') }}</span>
                    @if ($product->available !== null)
                        <span>{{ $product->available }}</span>
                    @endif
                    @if ($product->sku)
                        <span>{{ __('storefront::storefront.sku') }} {{ $product->sku }}</span>
                    @endif
                </p>

                @if ($product->inStock)
                    <form method="POST" action="{{ route('storefront.cart.items.store') }}" class="storefront-pdp__form">
                        @csrf
                        <input type="hidden" name="purchasable_uuid" value="{{ $product->variantUuid }}">
                        <label class="storefront-pdp__qty-field">
                            <span class="storefront-pdp__qty-label">{{ __('storefront::storefront.quantity') }}</span>
                            <input
                                type="number"
                                name="quantity"
                                value="1"
                                min="1"
                                @if ($product->available !== null) max="{{ $product->available }}" @endif
                                class="storefront-pdp__qty"
                            >
                        </label>
                        <button type="submit" class="storefront-pdp__add">
                            {{ __('storefront::storefront.add_to_cart') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-storefront.layout.page-container>
@endsection
