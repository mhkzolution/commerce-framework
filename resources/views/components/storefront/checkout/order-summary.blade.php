@props([
    'cart',
    'lines',
    'estimatedTotal',
    'cheapestShipping' => 0,
    'shippingQuotes' => [],
    'taxTotal' => 0,
    'estimatedDelivery' => null,
])

<aside {{ $attributes->merge(['class' => 'storefront-checkout-summary']) }} data-checkout-summary>
    <button
        type="button"
        class="storefront-checkout-summary__toggle"
        data-checkout-summary-toggle
        aria-expanded="false"
        aria-controls="checkout-summary-panel"
    >
        <span>{{ __('storefront::storefront.checkout_summary_toggle') }}</span>
        <span class="storefront-checkout-summary__toggle-total" data-checkout-summary-toggle-total>
            <x-storefront.price :amount="$estimatedTotal" :currency="$cart->currency" minor />
        </span>
    </button>

    <div id="checkout-summary-panel" class="storefront-checkout-summary__panel" data-checkout-summary-panel>
        <x-storefront.checkout.order-items :lines="$lines" :currency="$cart->currency" />

        <x-storefront.checkout.coupon-section :cart="$cart" class="storefront-checkout-summary__coupon" />

        <x-storefront.cart.summary-totals
            :cart="$cart"
            :estimated-total="$estimatedTotal"
            :cheapest-shipping="$cheapestShipping"
            :shipping-quotes="$shippingQuotes"
            class="storefront-checkout-summary__totals"
            data-checkout-totals
        />

        @if ($estimatedDelivery)
            <p class="storefront-checkout-summary__delivery">
                <span class="storefront-checkout-summary__delivery-label">{{ __('storefront::storefront.estimated_delivery') }}</span>
                {{ $estimatedDelivery }}
            </p>
        @endif

        <x-storefront.checkout.trust-section class="storefront-checkout-summary__trust" />

        <p class="storefront-checkout-summary__note">{{ __('storefront::storefront.checkout_stock_reserved') }}</p>
    </div>
</aside>
