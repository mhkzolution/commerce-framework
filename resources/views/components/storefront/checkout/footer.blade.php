@props([
    'cart',
    'estimatedTotal',
])

<div {{ $attributes->merge(['class' => 'storefront-checkout-footer']) }} data-checkout-footer>
    <div class="storefront-checkout-footer__inner">
        <div class="storefront-checkout-footer__total">
            <span class="storefront-checkout-footer__total-label">{{ __('storefront::storefront.grand_total') }}</span>
            <span class="storefront-checkout-footer__total-amount" data-checkout-footer-total>
                <x-storefront.price :amount="$estimatedTotal" :currency="$cart->currency" minor />
            </span>
        </div>
        <button type="submit" class="storefront-checkout-footer__submit cf-btn cf-btn--primary" form="checkout-form">
            {{ __('storefront::storefront.checkout_place_order') }}
        </button>
    </div>
</div>
