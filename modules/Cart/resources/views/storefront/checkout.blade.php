@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.checkout'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
    @vite('resources/js/storefront/checkout.js')
    @vite('resources/js/storefront/address.js')
@endpush

@section('content')
    <x-storefront.layout.page-container class="storefront-shopper storefront-checkout">
        <x-storefront.checkout.progress current="checkout" />
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
                $selectedShippingUuid = old(
                    'shipping_address_uuid',
                    $shippingAddresses->firstWhere('is_default_shipping', true)?->uuid ?? $shippingAddresses->first()?->uuid
                );
                $selectedBillingUuid = old(
                    'billing_address_uuid',
                    $billingAddresses->firstWhere('is_default_billing', true)?->uuid ?? $billingAddresses->first()?->uuid
                );
                $selectedShipping = $shippingAddresses->firstWhere('uuid', $selectedShippingUuid);
                $selectedBilling = $billingAddresses->firstWhere('uuid', $selectedBillingUuid);
                $recipientDefaults = [
                    'recipient_name' => old('customer_name', $customer?->name),
                    'phone' => old('customer_phone', $customer?->phone),
                ];
                $shippingPrefill = array_merge($recipientDefaults, $selectedShipping?->toOrderArray() ?? []);
                $billingPrefill = array_merge($recipientDefaults, $selectedBilling?->toOrderArray() ?? []);
                $shippingEditorOpen = ! $customer
                    || $shippingAddresses->isEmpty()
                    || filled(old('update_shipping_address_uuid'))
                    || (filled(old('shipping_address.line1')) && ! filled(old('shipping_address_uuid')));
                $billingEditorOpen = ! $customer
                    || $billingAddresses->isEmpty()
                    || filled(old('update_billing_address_uuid'))
                    || (filled(old('billing_address.line1')) && ! filled(old('billing_address_uuid')));
                $checkoutReturn = route('storefront.checkout');
                $itemCount = array_sum(array_map(fn ($line) => $line->quantity, $cart->lines));
                $shippingPrice = $shippingQuotes[0]->price ?? 0;
                $grandTotal = $cart->taxableSubtotal() + ($taxTotal ?? 0) + $shippingPrice;
                $paymentMethods = $paymentMethods ?? [];
            @endphp

            <div
                class="storefront-checkout__layout"
                data-checkout
                data-draft-url="{{ route('storefront.checkout.draft') }}"
                data-discount="{{ $cart->discountTotal }}"
                data-tax="{{ $taxTotal ?? 0 }}"
                data-currency="{{ $cart->currency }}"
            >
                <form
                    method="POST"
                    action="{{ route('storefront.checkout.store') }}"
                    class="storefront-checkout__form"
                    id="checkout-form"
                    data-checkout-form
                >
                    @csrf

                    <section class="storefront-checkout-block" aria-labelledby="checkout-contact-heading">
                        <h2 id="checkout-contact-heading" class="storefront-checkout-block__title">{{ __('storefront::storefront.contact_information') }}</h2>

                        @if ($customer)
                            <input type="hidden" name="customer_uuid" value="{{ $customer->uuid }}">
                            <div class="storefront-contact-card">
                                <p class="storefront-contact-card__name">{{ $customer->name }}</p>
                                <p>{{ $customer->email }}</p>
                                @if ($customer->phone)
                                    <p>{{ $customer->phone }}</p>
                                @else
                                    <div class="storefront-field">
                                        <label class="storefront-field__label" for="customer_phone">{{ __('storefront::storefront.phone') }}</label>
                                        <input id="customer_phone" type="tel" name="customer_phone" value="{{ old('customer_phone') }}" autocomplete="tel" inputmode="tel" class="storefront-input">
                                    </div>
                                @endif
                                <p class="storefront-contact-card__meta">
                                    <a href="{{ route('storefront.account') }}" class="storefront-link">{{ __('storefront::storefront.my_account') }}</a>
                                </p>
                            </div>
                        @else
                            <div class="storefront-form-grid">
                                <div class="storefront-field">
                                    <label class="storefront-field__label" for="customer_name">{{ __('storefront::storefront.name') }}</label>
                                    <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required autocomplete="name" class="storefront-input" data-contact-name>
                                </div>
                                <div class="storefront-field">
                                    <label class="storefront-field__label" for="customer_email">{{ __('storefront::storefront.email') }}</label>
                                    <input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email') }}" required autocomplete="email" class="storefront-input">
                                </div>
                                <div class="storefront-field storefront-form-grid__full">
                                    <label class="storefront-field__label" for="customer_phone">{{ __('storefront::storefront.phone') }}</label>
                                    <input id="customer_phone" type="tel" name="customer_phone" value="{{ old('customer_phone') }}" autocomplete="tel" inputmode="tel" class="storefront-input" data-contact-phone>
                                </div>
                            </div>
                            <p class="storefront-muted">
                                <a
                                    href="{{ route('storefront.account.login', ['redirect' => $checkoutReturn]) }}"
                                    class="storefront-link"
                                    data-checkout-auth
                                >{{ __('storefront::storefront.sign_in') }}</a>
                                {{ __('storefront::storefront.or') }}
                                <a
                                    href="{{ route('storefront.account.register', ['redirect' => $checkoutReturn]) }}"
                                    class="storefront-link"
                                    data-checkout-auth
                                >{{ __('storefront::storefront.create_account') }}</a>
                                {{ __('storefront::storefront.sign_in_saved_addresses') }}
                            </p>
                        @endif
                    </section>

                    <section class="storefront-checkout-block" aria-labelledby="checkout-shipping-heading">
                        <h2 id="checkout-shipping-heading" class="storefront-checkout-block__title">{{ __('storefront::storefront.shipping_address') }}</h2>
                        <div class="storefront-stack">
                            @if ($customer && $shippingAddresses->isNotEmpty())
                                @include('cart::storefront._saved_address_cards', [
                                    'addresses' => $shippingAddresses,
                                    'customer' => $customer,
                                    'role' => 'shipping',
                                    'selectedUuid' => $selectedShippingUuid,
                                ])
                            @endif

                            @include('cart::storefront._checkout_address_editor', [
                                'role' => 'shipping',
                                'prefix' => 'shipping_address',
                                'legend' => __('storefront::storefront.shipping_address'),
                                'prefill' => $shippingPrefill,
                                'required' => ! $customer || $shippingAddresses->isEmpty(),
                                'open' => $shippingEditorOpen,
                                'dismissable' => $customer && $shippingAddresses->isNotEmpty(),
                                'customer' => $customer,
                            ])
                        </div>
                    </section>

                    <section class="storefront-checkout-block" aria-labelledby="checkout-billing-heading">
                        <h2 id="checkout-billing-heading" class="storefront-checkout-block__title">{{ __('storefront::storefront.billing_address') }}</h2>
                        <div class="storefront-stack">
                            <label class="storefront-check storefront-check--card">
                                <input type="checkbox" name="billing_same_as_shipping" value="1" @checked(old('billing_same_as_shipping', true)) id="billing_same_as_shipping">
                                {{ __('storefront::storefront.billing_same_as_shipping') }}
                            </label>
                            <div id="billing-address-fields" @class(['storefront-is-hidden' => old('billing_same_as_shipping', true)])>
                                @if ($customer && $billingAddresses->isNotEmpty())
                                    @include('cart::storefront._saved_address_cards', [
                                        'addresses' => $billingAddresses,
                                        'customer' => $customer,
                                        'role' => 'billing',
                                        'selectedUuid' => $selectedBillingUuid,
                                    ])
                                @endif
                                @include('cart::storefront._checkout_address_editor', [
                                    'role' => 'billing',
                                    'prefix' => 'billing_address',
                                    'legend' => __('storefront::storefront.billing_address'),
                                    'prefill' => $billingPrefill,
                                    'required' => false,
                                    'open' => $billingEditorOpen,
                                    'dismissable' => $customer && $billingAddresses->isNotEmpty(),
                                    'customer' => $customer,
                                ])
                            </div>
                        </div>
                    </section>

                    @if ($shippingQuotes !== [])
                        <section class="storefront-checkout-block" aria-labelledby="checkout-shipping-method-heading">
                            <h2 id="checkout-shipping-method-heading" class="storefront-checkout-block__title">{{ __('storefront::storefront.shipping_method') }}</h2>
                            <fieldset class="storefront-shipping-list">
                                <legend class="storefront-visually-hidden">{{ __('storefront::storefront.shipping_method') }}</legend>
                                @foreach ($shippingQuotes as $index => $quote)
                                    <label class="storefront-shipping-card">
                                        <input
                                            type="radio"
                                            name="shipping_method_uuid"
                                            value="{{ $quote->uuid }}"
                                            data-price="{{ $quote->price }}"
                                            class="storefront-visually-hidden shipping-method-input"
                                            @checked(old('shipping_method_uuid', $shippingQuotes[0]->uuid ?? null) === $quote->uuid)
                                            @required($index === 0)
                                        >
                                        <span class="storefront-choice-mark" aria-hidden="true"></span>
                                        <span class="storefront-shipping-card__body">
                                            <span class="storefront-shipping-card__name">{{ $quote->name }}</span>
                                            @if ($quote->description)
                                                <span class="storefront-shipping-card__eta">{{ $quote->description }}</span>
                                            @endif
                                        </span>
                                        <span class="storefront-shipping-card__price">
                                            {{ $quote->price === 0 ? __('storefront::storefront.free') : number_format($quote->price / 100, 2).' '.$cart->currency }}
                                        </span>
                                    </label>
                                @endforeach
                            </fieldset>
                        </section>
                    @endif

                    <section class="storefront-checkout-block" aria-labelledby="checkout-payment-heading">
                        <h2 id="checkout-payment-heading" class="storefront-checkout-block__title">{{ __('storefront::storefront.payment') }}</h2>
                        <p class="storefront-muted">{{ __('storefront::storefront.payment_next_step_hint') }}</p>
                        <ul class="storefront-payment-preview">
                            @forelse ($paymentMethods as $method)
                                <li class="storefront-payment-preview__item">{{ $method['name'] }}</li>
                            @empty
                                <li class="storefront-payment-preview__item">{{ __('storefront::storefront.payment_method_card') }}</li>
                                <li class="storefront-payment-preview__item">{{ __('storefront::storefront.payment_method_secure') }}</li>
                            @endforelse
                        </ul>
                    </section>

                    <div class="storefront-checkout__actions">
                        <div class="storefront-checkout__actions-total">
                            <span class="storefront-muted">{{ __('storefront::storefront.total') }}</span>
                            <strong id="checkout-total-mobile">{{ number_format($grandTotal / 100, 2) }} {{ $cart->currency }}</strong>
                        </div>
                        <button type="submit" class="storefront-btn storefront-btn--block">{{ __('storefront::storefront.continue_to_payment') }}</button>
                    </div>
                </form>

                <aside class="storefront-checkout__summary storefront-checkout__summary--sticky" data-checkout-summary>
                    <button
                        type="button"
                        class="storefront-checkout__summary-toggle"
                        data-summary-toggle
                        aria-expanded="false"
                    >
                        <span>{{ __('storefront::storefront.order_summary') }}</span>
                        <span class="storefront-checkout__summary-toggle-total">{{ number_format($grandTotal / 100, 2) }} {{ $cart->currency }}</span>
                    </button>
                    <div class="storefront-checkout__summary-body">
                        <h2 class="storefront-panel__title">{{ __('storefront::storefront.order_summary') }}</h2>
                        <ul class="storefront-summary-lines">
                            @foreach ($cart->lines as $line)
                                <li class="storefront-summary-line">
                                    <div class="storefront-summary-line__media">
                                        @if ($line->imageUrl)
                                            <x-storefront.media.img
                                                :src="$line->imageUrl"
                                                :srcset="$line->imageSrcset"
                                                :sizes="config('media.sizes.cart')"
                                                alt=""
                                                class="storefront-summary-line__image"
                                            />
                                        @else
                                            <div class="storefront-summary-line__placeholder">{{ __('storefront::storefront.no_image') }}</div>
                                        @endif
                                        <span class="storefront-summary-line__qty-badge">{{ $line->quantity }}</span>
                                    </div>
                                    <div class="storefront-summary-line__details">
                                        @if ($line->url)
                                            <a href="{{ $line->url }}" class="storefront-summary-line__name">{{ $line->productName ?? $line->name }}</a>
                                        @else
                                            <span class="storefront-summary-line__name">{{ $line->productName ?? $line->name }}</span>
                                        @endif
                                        @if ($line->variantLabel)
                                            <span class="storefront-muted">{{ $line->variantLabel }}</span>
                                        @endif
                                        @if ($line->sku)
                                            <span class="storefront-muted">{{ __('storefront::storefront.sku') }} {{ $line->sku }}</span>
                                        @endif
                                        <span class="storefront-muted">{{ __('storefront::storefront.qty') }} {{ $line->quantity }}</span>
                                    </div>
                                    <span class="storefront-summary-line__price">{{ number_format($line->lineTotal / 100, 2) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="storefront-summary-totals">
                            <div class="storefront-summary-row">
                                <span class="storefront-muted">{{ trans_choice('storefront::storefront.subtotal_items', $itemCount, ['count' => $itemCount]) }}</span>
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
                                    <span id="checkout-shipping" data-free="{{ __('storefront::storefront.free') }}">{{ $shippingPrice === 0 ? __('storefront::storefront.free') : number_format($shippingPrice / 100, 2) }}</span>
                                </div>
                            @endif
                            <div class="storefront-summary-row storefront-summary-row--total">
                                <span>{{ __('storefront::storefront.total') }}</span>
                                <span id="checkout-total">{{ number_format($grandTotal / 100, 2) }} {{ $cart->currency }}</span>
                            </div>
                        </div>
                        <p class="storefront-muted">{{ __('storefront::storefront.stock_reserved_after_payment') }}</p>
                    </div>
                </aside>
            </div>
        @endif
    </x-storefront.layout.page-container>
@endsection
