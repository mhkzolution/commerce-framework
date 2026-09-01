@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.checkout_title'))

@section('content')
    @if ($errors->any())
        <div class="cf-flash cf-flash--danger storefront-checkout__flash">
            @if ($errors->has('checkout'))
                {{ $errors->first('checkout') }}
            @else
                <ul class="storefront-checkout__error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    @if ($cart->lines === [])
        <div class="storefront-checkout storefront-checkout--empty">
            <h1 class="storefront-checkout__title">{{ __('storefront::storefront.checkout_title') }}</h1>
            <x-storefront.empty-state
                :title="__('storefront::storefront.cart_empty_title')"
                :description="__('storefront::storefront.cart_empty_description')"
            >
                <x-admin.button :href="route('storefront.shop.index')" variant="primary">
                    {{ __('storefront::storefront.continue_shopping') }}
                </x-admin.button>
            </x-storefront.empty-state>
        </div>
    @else
        <x-storefront.checkout.layout
            :cart="$cart"
            :lines="$lines"
            :customer="$customer"
            :addresses="$addresses"
            :shipping-quotes="$shippingQuotes"
            :tax-total="$taxTotal"
            :estimated-total="$estimatedTotal"
            :cheapest-shipping="$cheapestShipping"
            :estimated-delivery="$estimatedDelivery"
            :show-manual-shipping="$showManualShipping"
            :show-manual-billing="$showManualBilling"
        />
    @endif
@endsection

@push('scripts')
    @vite('resources/js/storefront/checkout.js')
@endpush
