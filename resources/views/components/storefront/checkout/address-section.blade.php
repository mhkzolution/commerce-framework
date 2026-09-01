@props([
    'customer' => null,
    'addresses',
    'showManualShipping' => true,
    'showManualBilling' => true,
])

<x-storefront.checkout.section
    :title="__('storefront::storefront.checkout_shipping_address')"
    step="2"
>
    @php
        $shippingAddresses = $addresses->filter(fn ($address) => in_array($address->type, ['shipping', 'both'], true));
        $billingAddresses = $addresses->filter(fn ($address) => in_array($address->type, ['billing', 'both'], true));
    @endphp

    @if ($customer && $shippingAddresses->isNotEmpty())
        <fieldset class="storefront-checkout-address-picker">
            <legend class="sr-only">{{ __('storefront::storefront.checkout_use_saved_address') }}</legend>
            <div class="storefront-checkout-address-picker__list">
                @foreach ($shippingAddresses as $address)
                    <label class="storefront-checkout-address-card">
                        <input
                            type="radio"
                            name="shipping_address_uuid"
                            value="{{ $address->uuid }}"
                            class="storefront-checkout-address-card__input"
                            data-checkout-saved-address
                            @checked(old('shipping_address_uuid', $shippingAddresses->firstWhere('is_default', true)?->uuid ?? $shippingAddresses->first()?->uuid) === $address->uuid)
                        >
                        <span class="storefront-checkout-address-card__body">
                            <span class="storefront-checkout-address-card__label">
                                {{ $address->label ?: __('storefront::storefront.address') }}
                                @if ($address->is_default)
                                    <span class="storefront-checkout-address-card__badge">{{ __('storefront::storefront.default') }}</span>
                                @endif
                            </span>
                            <span class="storefront-checkout-address-card__lines">
                                {{ $address->line1 }}@if ($address->line2), {{ $address->line2 }}@endif<br>
                                {{ $address->city }}@if ($address->state), {{ $address->state }}@endif {{ $address->postal_code }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <button
            type="button"
            class="storefront-checkout-address-toggle"
            data-checkout-address-toggle="manual-shipping"
            aria-expanded="{{ $showManualShipping ? 'true' : 'false' }}"
        >
            {{ __('storefront::storefront.checkout_enter_new_address') }}
        </button>
    @endif

    <div
        id="manual-shipping-address"
        class="storefront-checkout-address-manual"
        @if ($customer && $shippingAddresses->isNotEmpty() && ! $showManualShipping) hidden @endif
        data-checkout-manual-shipping
    >
        <x-storefront.checkout.address-fields prefix="shipping_address" />
    </div>

    @if ($customer && $billingAddresses->isNotEmpty())
        <div class="storefront-checkout-billing">
            <h3 class="storefront-checkout-billing__title">{{ __('storefront::storefront.checkout_billing_address') }}</h3>
            <label class="storefront-checkout-billing__same">
                <input
                    type="checkbox"
                    name="billing_same_as_shipping"
                    value="1"
                    id="billing_same_as_shipping"
                    data-checkout-billing-same
                    @checked(old('billing_same_as_shipping', true))
                >
                <span>{{ __('storefront::storefront.checkout_same_as_shipping') }}</span>
            </label>

            <div id="billing-address-picker" class="storefront-checkout-address-picker" data-checkout-billing-picker @if (old('billing_same_as_shipping', true)) hidden @endif>
                @foreach ($billingAddresses as $address)
                    <label class="storefront-checkout-address-card">
                        <input
                            type="radio"
                            name="billing_address_uuid"
                            value="{{ $address->uuid }}"
                            class="storefront-checkout-address-card__input"
                            @checked(old('billing_address_uuid', $billingAddresses->firstWhere('is_default', true)?->uuid ?? $billingAddresses->first()?->uuid) === $address->uuid)
                        >
                        <span class="storefront-checkout-address-card__body">
                            <span class="storefront-checkout-address-card__label">{{ $address->label ?: __('storefront::storefront.address') }}</span>
                            <span class="storefront-checkout-address-card__lines">
                                {{ $address->line1 }}, {{ $address->city }} {{ $address->postal_code }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    @elseif ($showManualBilling)
        <div class="storefront-checkout-billing">
            <label class="storefront-checkout-billing__same">
                <input
                    type="checkbox"
                    name="billing_same_as_shipping"
                    value="1"
                    id="billing_same_as_shipping"
                    data-checkout-billing-same
                    @checked(old('billing_same_as_shipping', true))
                >
                <span>{{ __('storefront::storefront.checkout_billing_same_as_shipping') }}</span>
            </label>

            <div id="billing-address-fields" data-checkout-billing-fields @class(['storefront-checkout-address-manual', 'hidden' => old('billing_same_as_shipping', true)])>
                <x-storefront.checkout.address-fields prefix="billing_address" />
            </div>
        </div>
    @endif
</x-storefront.checkout.section>
