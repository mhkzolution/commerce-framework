@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.checkout'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    <x-storefront.layout.page-container class="storefront-shopper storefront-checkout">
        <h1 class="storefront-shopper__title">{{ __('storefront::storefront.checkout') }}</h1>

        @if ($errors->any())
            <div class="storefront-flash storefront-flash--danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($cart->lines === [])
            <x-storefront.empty-state :title="__('storefront::storefront.cart_empty')">
                <a href="{{ route('storefront.shop.index') }}" class="storefront-link">{{ __('storefront::storefront.continue_shopping') }}</a>
            </x-storefront.empty-state>
        @else
            @php
                $shippingAddresses = $addresses->filter(fn ($address) => in_array($address->type, ['shipping', 'both'], true));
                $billingAddresses = $addresses->filter(fn ($address) => in_array($address->type, ['billing', 'both'], true));
                $showManualShipping = ! $customer || $shippingAddresses->isEmpty();
                $showManualBilling = ! $customer || $billingAddresses->isEmpty();
            @endphp

            <div class="storefront-checkout__layout">
                <form method="POST" action="{{ route('storefront.checkout.store') }}" class="storefront-panel storefront-stack" id="checkout-form">
                    @csrf

                    @if ($customer)
                        <p class="storefront-muted">
                            {{ __('storefront::storefront.signed_in_as') }}
                            <strong>{{ $customer->name }}</strong>
                            (<a href="{{ route('storefront.account') }}" class="storefront-link">{{ __('storefront::storefront.my_account') }}</a>)
                        </p>
                        <input type="hidden" name="customer_uuid" value="{{ $customer->uuid }}">
                    @else
                        <div class="storefront-field">
                            <label class="storefront-field__label" for="customer_name">{{ __('storefront::storefront.name') }}</label>
                            <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required class="storefront-input">
                        </div>
                        <div class="storefront-field">
                            <label class="storefront-field__label" for="customer_email">{{ __('storefront::storefront.email') }}</label>
                            <input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email') }}" required class="storefront-input">
                        </div>
                        <p class="storefront-muted">
                            <a href="{{ route('storefront.account.login') }}" class="storefront-link">{{ __('storefront::storefront.sign_in') }}</a>
                            {{ __('storefront::storefront.sign_in_saved_addresses') }}
                        </p>
                    @endif

                    @if ($customer && $shippingAddresses->isNotEmpty())
                        <fieldset class="storefront-stack">
                            <legend class="storefront-field__label">{{ __('storefront::storefront.shipping_address') }}</legend>
                            @foreach ($shippingAddresses as $address)
                                <label class="storefront-choice">
                                    <input type="radio" name="shipping_address_uuid" value="{{ $address->uuid }}" @checked(old('shipping_address_uuid', $shippingAddresses->firstWhere('is_default', true)?->uuid ?? $shippingAddresses->first()?->uuid) === $address->uuid)>
                                    <span>
                                        <span>{{ $address->label ?: __('storefront::storefront.address') }}</span><br>
                                        {{ $address->line1 }}, {{ $address->city }} {{ $address->postal_code }}
                                    </span>
                                </label>
                            @endforeach
                        </fieldset>
                    @endif

                    @if ($showManualShipping)
                        @include('cart::storefront._checkout_address_fields', ['prefix' => 'shipping_address', 'legend' => __('storefront::storefront.shipping_address')])
                    @endif

                    @if ($customer && $billingAddresses->isNotEmpty())
                        <fieldset class="storefront-stack">
                            <legend class="storefront-field__label">{{ __('storefront::storefront.billing_address') }}</legend>
                            <label class="storefront-check">
                                <input type="checkbox" name="billing_same_as_shipping" value="1" @checked(old('billing_same_as_shipping', true)) id="billing_same_as_shipping">
                                {{ __('storefront::storefront.same_as_shipping') }}
                            </label>
                            <div id="billing-address-picker" class="storefront-stack">
                                @foreach ($billingAddresses as $address)
                                    <label class="storefront-choice">
                                        <input type="radio" name="billing_address_uuid" value="{{ $address->uuid }}" @checked(old('billing_address_uuid', $billingAddresses->firstWhere('is_default', true)?->uuid ?? $billingAddresses->first()?->uuid) === $address->uuid)>
                                        <span>
                                            <span>{{ $address->label ?: __('storefront::storefront.address') }}</span><br>
                                            {{ $address->line1 }}, {{ $address->city }} {{ $address->postal_code }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @elseif ($showManualBilling)
                        <label class="storefront-check">
                            <input type="checkbox" name="billing_same_as_shipping" value="1" @checked(old('billing_same_as_shipping', true)) id="billing_same_as_shipping">
                            {{ __('storefront::storefront.billing_same_as_shipping') }}
                        </label>
                        <div id="billing-address-fields" @class(['storefront-is-hidden' => old('billing_same_as_shipping', true)])>
                            @include('cart::storefront._checkout_address_fields', ['prefix' => 'billing_address', 'legend' => __('storefront::storefront.billing_address')])
                        </div>
                    @endif

                    @if ($shippingQuotes !== [])
                        <fieldset class="storefront-stack">
                            <legend class="storefront-field__label">{{ __('storefront::storefront.shipping_method') }}</legend>
                            @foreach ($shippingQuotes as $index => $quote)
                                <label class="storefront-choice">
                                    <input
                                        type="radio"
                                        name="shipping_method_uuid"
                                        value="{{ $quote->uuid }}"
                                        data-price="{{ $quote->price }}"
                                        class="shipping-method-input"
                                        @checked(old('shipping_method_uuid', $shippingQuotes[0]->uuid ?? null) === $quote->uuid)
                                        @required($index === 0)
                                    >
                                    <span>
                                        <span>{{ $quote->name }}</span>
                                        @if ($quote->description)
                                            <span class="storefront-muted">{{ $quote->description }}</span>
                                        @endif
                                    </span>
                                    <span>
                                        {{ $quote->price === 0 ? __('storefront::storefront.free') : number_format($quote->price / 100, 2) }}
                                    </span>
                                </label>
                            @endforeach
                        </fieldset>
                    @endif

                    <button type="submit" class="storefront-btn storefront-btn--block">{{ __('storefront::storefront.continue_to_payment') }}</button>
                </form>

                <section class="storefront-panel storefront-stack">
                    <h2 class="storefront-panel__title">{{ __('storefront::storefront.order_summary') }}</h2>
                    <ul class="storefront-stack">
                        @foreach ($cart->lines as $line)
                            <li class="storefront-summary-row">
                                <span>{{ $line->name }} × {{ $line->quantity }}</span>
                                <span>{{ number_format($line->lineTotal / 100, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="storefront-stack">
                        <div class="storefront-summary-row">
                            <span class="storefront-muted">{{ __('storefront::storefront.subtotal') }}</span>
                            <span id="checkout-subtotal" data-amount="{{ $cart->subtotal }}">{{ number_format($cart->subtotal / 100, 2) }}</span>
                        </div>
                        @if ($cart->discountTotal > 0)
                            <div class="storefront-summary-row storefront-success">
                                <span>{{ __('storefront::storefront.discount') }} ({{ $cart->couponCode }})</span>
                                <span>-{{ number_format($cart->discountTotal / 100, 2) }}</span>
                            </div>
                        @endif
                        @if (($taxTotal ?? 0) > 0)
                            <div class="storefront-summary-row">
                                <span class="storefront-muted">{{ __('storefront::storefront.tax_est') }}</span>
                                <span id="checkout-tax">{{ number_format($taxTotal / 100, 2) }}</span>
                            </div>
                        @endif
                        @if ($shippingQuotes !== [])
                            <div class="storefront-summary-row">
                                <span class="storefront-muted">{{ __('storefront::storefront.shipping') }}</span>
                                <span id="checkout-shipping" data-free="{{ __('storefront::storefront.free') }}">{{ number_format(($shippingQuotes[0]->price ?? 0) / 100, 2) }}</span>
                            </div>
                        @endif
                        <div class="storefront-summary-row">
                            <span>{{ __('storefront::storefront.total') }}</span>
                            <span id="checkout-total">{{ number_format(($cart->taxableSubtotal() + ($taxTotal ?? 0) + ($shippingQuotes[0]->price ?? 0)) / 100, 2) }} {{ $cart->currency }}</span>
                        </div>
                    </div>
                    <p class="storefront-muted">{{ __('storefront::storefront.stock_reserved_after_payment') }}</p>
                </section>
            </div>
        @endif
    </x-storefront.layout.page-container>
@endsection

@push('scripts')
    <script>
        const sameAsShipping = document.getElementById('billing_same_as_shipping');
        const billingFields = document.getElementById('billing-address-fields');
        const billingPicker = document.getElementById('billing-address-picker');

        if (sameAsShipping) {
            sameAsShipping.addEventListener('change', () => {
                if (billingFields) {
                    billingFields.classList.toggle('storefront-is-hidden', sameAsShipping.checked);
                }
                if (billingPicker) {
                    billingPicker.classList.toggle('storefront-is-hidden', sameAsShipping.checked);
                }
            });

            if (billingPicker && sameAsShipping.checked) {
                billingPicker.classList.add('storefront-is-hidden');
            }
        }

        const subtotalEl = document.getElementById('checkout-subtotal');
        const shippingEl = document.getElementById('checkout-shipping');
        const totalEl = document.getElementById('checkout-total');
        const shippingInputs = document.querySelectorAll('.shipping-method-input');

        function updateCheckoutTotal() {
            if (!subtotalEl || !totalEl) {
                return;
            }

            const subtotal = parseInt(subtotalEl.dataset.amount || '0', 10);
            const discount = {{ $cart->discountTotal }};
            const tax = {{ $taxTotal ?? 0 }};
            let shipping = 0;
            shippingInputs.forEach((input) => {
                if (input.checked) {
                    shipping = parseInt(input.dataset.price || '0', 10);
                }
            });

            if (shippingEl) {
                const freeLabel = shippingEl.dataset.free || '';
                shippingEl.textContent = shipping === 0 ? freeLabel : (shipping / 100).toFixed(2);
            }

            totalEl.textContent = ((subtotal - discount + tax + shipping) / 100).toFixed(2) + ' {{ $cart->currency }}';
        }

        shippingInputs.forEach((input) => input.addEventListener('change', updateCheckoutTotal));
        updateCheckoutTotal();
    </script>
@endpush
