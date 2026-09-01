<div {{ $attributes->merge(['class' => 'storefront-checkout-trust']) }}>
    <p class="storefront-checkout-trust__secure">
        <span class="storefront-checkout-trust__secure-icon" aria-hidden="true"></span>
        {{ __('storefront::storefront.secure_checkout') }}
    </p>
    <p class="storefront-checkout-trust__policy">{{ __('storefront::storefront.checkout_return_policy') }}</p>
    <x-storefront.cart.payment-icons class="storefront-checkout-trust__payments" />
    <x-storefront.cart.trust-indicators class="storefront-checkout-trust__badges" />
</div>
