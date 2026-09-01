@props([
    'cart',
    'lines',
    'customer' => null,
    'addresses',
    'shippingQuotes' => [],
    'taxTotal' => 0,
    'estimatedTotal' => 0,
    'cheapestShipping' => 0,
    'estimatedDelivery' => null,
    'showManualShipping' => true,
    'showManualBilling' => true,
])

@php
    $initialShipping = collect($shippingQuotes)->first();
    $shippingPrice = $initialShipping->price ?? $cheapestShipping;
    $computedTotal = $cart->taxableSubtotal() + ($taxTotal ?? 0) + $shippingPrice;
@endphp

<div
    {{ $attributes->merge(['class' => 'storefront-checkout']) }}
    data-checkout
    data-currency="{{ $cart->currency }}"
    data-currency-symbol="{{ \Commerce\Cart\Support\StorefrontMoney::meta((string) $cart->currency)['symbol'] }}"
    data-subtotal="{{ $cart->subtotal }}"
    data-discount="{{ $cart->discountTotal }}"
    data-tax="{{ $taxTotal ?? 0 }}"
    data-shipping="{{ $shippingPrice }}"
    data-shipping-free-label="{{ __('storefront::storefront.shipping_free') }}"
>
    <header class="storefront-checkout__header">
        <x-storefront.breadcrumb :items="[
            ['label' => __('storefront::storefront.shop'), 'url' => route('storefront.shop.index')],
            ['label' => __('storefront::storefront.cart_title'), 'url' => route('storefront.cart.index')],
            ['label' => __('storefront::storefront.checkout_title')],
        ]" />

        <h1 class="storefront-checkout__title">{{ __('storefront::storefront.checkout_title') }}</h1>

        <x-storefront.checkout.progress current="checkout" />
    </header>

    <div class="storefront-checkout__layout">
        <div class="storefront-checkout__main">
            <form
                method="POST"
                action="{{ route('storefront.checkout.store') }}"
                id="checkout-form"
                class="storefront-checkout__form"
                data-checkout-form
            >
                @csrf

                <x-storefront.checkout.contact-section :customer="$customer" />

                <x-storefront.checkout.address-section
                    :customer="$customer"
                    :addresses="$addresses"
                    :show-manual-shipping="$showManualShipping"
                    :show-manual-billing="$showManualBilling"
                />

                <x-storefront.checkout.shipping-section
                    :shipping-quotes="$shippingQuotes"
                    :currency="$cart->currency"
                />

                <x-storefront.checkout.payment-section />

                <x-storefront.checkout.notes-section />

                <div class="storefront-checkout__submit-desktop">
                    <button type="submit" class="storefront-checkout__submit cf-btn cf-btn--primary">
                        {{ __('storefront::storefront.checkout_continue_payment') }}
                    </button>
                </div>
            </form>
        </div>

        <x-storefront.checkout.order-summary
            :cart="$cart"
            :lines="$lines"
            :estimated-total="$computedTotal"
            :cheapest-shipping="$cheapestShipping"
            :shipping-quotes="$shippingQuotes"
            :tax-total="$taxTotal"
            :estimated-delivery="$estimatedDelivery"
        />
    </div>

    <x-storefront.checkout.footer :cart="$cart" :estimated-total="$computedTotal" />
</div>
