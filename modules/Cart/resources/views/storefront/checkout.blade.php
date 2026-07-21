@extends('cart::layouts.storefront')

@section('title', 'Checkout')

@section('content')
    <h1 class="text-2xl font-semibold text-text">Checkout</h1>

    @if ($errors->any())
        <div class="cf-flash cf-flash--danger mt-4">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($cart->lines === [])
        <p class="mt-8 text-muted">Your cart is empty.</p>
    @else
        @php
            $shippingAddresses = $addresses->filter(fn ($address) => in_array($address->type, ['shipping', 'both'], true));
            $billingAddresses = $addresses->filter(fn ($address) => in_array($address->type, ['billing', 'both'], true));
            $showManualShipping = ! $customer || $shippingAddresses->isEmpty();
            $showManualBilling = ! $customer || $billingAddresses->isEmpty();
        @endphp

        <div class="mt-6 grid gap-8 lg:grid-cols-2">
            <form method="POST" action="{{ route('storefront.checkout.store') }}" class="space-y-6 rounded-lg border border-border bg-surface p-6 shadow-sm" id="checkout-form">
                @csrf

                @if ($customer)
                    <p class="rounded-md bg-surface-muted p-3 text-sm text-text-secondary">
                        Signed in as <strong class="text-text">{{ $customer->name }}</strong>
                        (<a href="{{ route('storefront.account') }}" class="text-link underline">My account</a>)
                    </p>
                    <input type="hidden" name="customer_uuid" value="{{ $customer->uuid }}">
                @else
                    <div>
                        <label class="block text-sm font-medium text-text" for="customer_name">Name</label>
                        <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required class="cf-input mt-1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text" for="customer_email">Email</label>
                        <input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email') }}" required class="cf-input mt-1">
                    </div>
                    <p class="text-sm text-muted">
                        <a href="{{ route('storefront.account.login') }}" class="text-link underline">Sign in</a>
                        to use saved addresses.
                    </p>
                @endif

                @if ($customer && $shippingAddresses->isNotEmpty())
                    <fieldset>
                        <legend class="text-sm font-medium text-text">Shipping address</legend>
                        <div class="mt-2 space-y-2">
                            @foreach ($shippingAddresses as $address)
                                <label class="flex cursor-pointer gap-3 rounded-md border border-border p-3 text-sm hover:bg-surface-muted">
                                    <input type="radio" name="shipping_address_uuid" value="{{ $address->uuid }}" @checked(old('shipping_address_uuid', $shippingAddresses->firstWhere('is_default', true)?->uuid ?? $shippingAddresses->first()?->uuid) === $address->uuid)>
                                    <span>
                                        <span class="font-medium text-text">{{ $address->label ?: 'Address' }}</span><br>
                                        {{ $address->line1 }}, {{ $address->city }} {{ $address->postal_code }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                @if ($showManualShipping)
                    @include('cart::storefront._checkout_address_fields', ['prefix' => 'shipping_address', 'legend' => 'Shipping address'])
                @endif

                @if ($customer && $billingAddresses->isNotEmpty())
                    <fieldset>
                        <legend class="text-sm font-medium text-text">Billing address</legend>
                        <label class="mt-2 flex items-center gap-2 text-sm text-text-secondary">
                            <input type="checkbox" name="billing_same_as_shipping" value="1" @checked(old('billing_same_as_shipping', true)) id="billing_same_as_shipping" class="rounded border-border">
                            Same as shipping
                        </label>
                        <div id="billing-address-picker" class="mt-2 space-y-2">
                            @foreach ($billingAddresses as $address)
                                <label class="flex cursor-pointer gap-3 rounded-md border border-border p-3 text-sm hover:bg-surface-muted">
                                    <input type="radio" name="billing_address_uuid" value="{{ $address->uuid }}" @checked(old('billing_address_uuid', $billingAddresses->firstWhere('is_default', true)?->uuid ?? $billingAddresses->first()?->uuid) === $address->uuid)>
                                    <span>
                                        <span class="font-medium text-text">{{ $address->label ?: 'Address' }}</span><br>
                                        {{ $address->line1 }}, {{ $address->city }} {{ $address->postal_code }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @elseif ($showManualBilling)
                    <label class="flex items-center gap-2 text-sm text-text-secondary">
                        <input type="checkbox" name="billing_same_as_shipping" value="1" @checked(old('billing_same_as_shipping', true)) id="billing_same_as_shipping" class="rounded border-border">
                        Billing address same as shipping
                    </label>
                    <div id="billing-address-fields" @class(['hidden' => old('billing_same_as_shipping', true)])>
                        @include('cart::storefront._checkout_address_fields', ['prefix' => 'billing_address', 'legend' => 'Billing address'])
                    </div>
                @endif

                @if ($shippingQuotes !== [])
                    <fieldset>
                        <legend class="text-sm font-medium text-text">Shipping method</legend>
                        <div class="mt-2 space-y-2">
                            @foreach ($shippingQuotes as $index => $quote)
                                <label class="flex cursor-pointer gap-3 rounded-md border border-border p-3 text-sm hover:bg-surface-muted">
                                    <input
                                        type="radio"
                                        name="shipping_method_uuid"
                                        value="{{ $quote->uuid }}"
                                        data-price="{{ $quote->price }}"
                                        class="shipping-method-input"
                                        @checked(old('shipping_method_uuid', $shippingQuotes[0]->uuid ?? null) === $quote->uuid)
                                        @required($index === 0)
                                    >
                                    <span class="flex-1">
                                        <span class="font-medium text-text">{{ $quote->name }}</span>
                                        @if ($quote->description)
                                            <span class="block text-muted">{{ $quote->description }}</span>
                                        @endif
                                    </span>
                                    <span class="font-medium text-text">
                                        {{ $quote->price === 0 ? 'Free' : number_format($quote->price / 100, 2) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <button type="submit" class="cf-btn cf-btn--primary w-full">Continue to payment</button>
            </form>

            <section class="rounded-lg border border-border bg-surface p-6 shadow-sm">
                <h2 class="text-lg font-medium text-text">Order summary</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($cart->lines as $line)
                        <li class="flex justify-between">
                            <span>{{ $line->name }} × {{ $line->quantity }}</span>
                            <span>{{ number_format($line->lineTotal / 100, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 space-y-2 border-t border-border pt-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted">Subtotal</span>
                        <span id="checkout-subtotal" data-amount="{{ $cart->subtotal }}">{{ number_format($cart->subtotal / 100, 2) }}</span>
                    </div>
                    @if ($cart->discountTotal > 0)
                        <div class="flex justify-between text-success">
                            <span>Discount ({{ $cart->couponCode }})</span>
                            <span>-{{ number_format($cart->discountTotal / 100, 2) }}</span>
                        </div>
                    @endif
                    @if (($taxTotal ?? 0) > 0)
                        <div class="flex justify-between">
                            <span class="text-muted">Tax (est.)</span>
                            <span id="checkout-tax">{{ number_format($taxTotal / 100, 2) }}</span>
                        </div>
                    @endif
                    @if ($shippingQuotes !== [])
                        <div class="flex justify-between">
                            <span class="text-muted">Shipping</span>
                            <span id="checkout-shipping">{{ number_format(($shippingQuotes[0]->price ?? 0) / 100, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-medium text-text">
                        <span>Total</span>
                        <span id="checkout-total">{{ number_format(($cart->taxableSubtotal() + ($taxTotal ?? 0) + ($shippingQuotes[0]->price ?? 0)) / 100, 2) }} {{ $cart->currency }}</span>
                    </div>
                </div>
                <p class="mt-4 text-xs text-muted">Stock is reserved after payment is completed.</p>
            </section>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        const sameAsShipping = document.getElementById('billing_same_as_shipping');
        const billingFields = document.getElementById('billing-address-fields');
        const billingPicker = document.getElementById('billing-address-picker');

        if (sameAsShipping) {
            sameAsShipping.addEventListener('change', () => {
                if (billingFields) {
                    billingFields.classList.toggle('hidden', sameAsShipping.checked);
                }
                if (billingPicker) {
                    billingPicker.classList.toggle('hidden', sameAsShipping.checked);
                }
            });

            if (billingPicker && sameAsShipping.checked) {
                billingPicker.classList.add('hidden');
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
                shippingEl.textContent = shipping === 0 ? 'Free' : (shipping / 100).toFixed(2);
            }

            totalEl.textContent = ((subtotal - discount + tax + shipping) / 100).toFixed(2) + ' {{ $cart->currency }}';
        }

        shippingInputs.forEach((input) => input.addEventListener('change', updateCheckoutTotal));
        updateCheckoutTotal();
    </script>
@endpush
