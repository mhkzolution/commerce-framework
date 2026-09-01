@props([
    'customer' => null,
])

<x-storefront.checkout.section
    :title="__('storefront::storefront.checkout_contact')"
    :description="__('storefront::storefront.checkout_contact_description')"
    step="1"
>
    @if ($customer)
        <div class="storefront-checkout-contact__signed-in">
            <p class="storefront-checkout-contact__greeting">
                {{ __('storefront::storefront.checkout_signed_in_as', ['name' => $customer->name]) }}
            </p>
            <a href="{{ route('storefront.account') }}" class="storefront-checkout-contact__account-link">
                {{ __('storefront::storefront.checkout_my_account') }}
            </a>
            <input type="hidden" name="customer_uuid" value="{{ $customer->uuid }}">
        </div>

        <div class="storefront-checkout-field-grid">
            <x-storefront.checkout.field
                id="customer_email"
                name="customer_email"
                type="email"
                :label="__('storefront::storefront.email')"
                :value="old('customer_email', $customer->email)"
                autocomplete="email"
            />
            <x-storefront.checkout.field
                id="customer_phone"
                name="customer_phone"
                type="tel"
                :label="__('storefront::storefront.phone')"
                :value="old('customer_phone', $customer->phone)"
                autocomplete="tel"
                :required="false"
            />
        </div>
    @else
        <p class="storefront-checkout-contact__guest">
            <span class="storefront-checkout-contact__guest-label">{{ __('storefront::storefront.checkout_guest_checkout') }}</span>
            <a href="{{ route('storefront.account.login') }}" class="storefront-checkout-contact__sign-in">
                {{ __('storefront::storefront.sign_in') }}
            </a>
            <span class="storefront-checkout-contact__hint">{{ __('storefront::storefront.checkout_sign_in_hint') }}</span>
        </p>

        <div class="storefront-checkout-field-grid">
            <x-storefront.checkout.field
                id="customer_name"
                name="customer_name"
                :label="__('storefront::storefront.checkout_name')"
                :value="old('customer_name')"
                autocomplete="name"
                :required="true"
                class="storefront-checkout-field--full"
            />
            <x-storefront.checkout.field
                id="customer_email"
                name="customer_email"
                type="email"
                :label="__('storefront::storefront.email')"
                :value="old('customer_email')"
                autocomplete="email"
                :required="true"
            />
            <x-storefront.checkout.field
                id="customer_phone"
                name="customer_phone"
                type="tel"
                :label="__('storefront::storefront.phone')"
                :value="old('customer_phone')"
                autocomplete="tel"
                :required="false"
            />
        </div>
    @endif
</x-storefront.checkout.section>
