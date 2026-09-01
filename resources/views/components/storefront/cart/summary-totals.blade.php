@props([
    'cart',
    'estimatedTotal',
    'cheapestShipping' => 0,
    'shippingQuotes' => [],
])

<div {{ $attributes->merge(['class' => 'storefront-cart-summary']) }} data-cart-summary>
    <h2 class="storefront-cart-summary__title">{{ __('storefront::storefront.cart_summary') }}</h2>

    <dl class="storefront-cart-summary__rows">
        <div class="storefront-cart-summary__row">
            <dt>{{ __('storefront::storefront.subtotal') }}</dt>
            <dd data-cart-summary-subtotal>
                <x-storefront.price :amount="$cart->subtotal" :currency="$cart->currency" minor />
                <span class="storefront-cart-summary__meta" data-cart-summary-count>
                    {{ trans_choice('storefront::storefront.items_count', $cart->itemCount, ['count' => $cart->itemCount]) }}
                </span>
            </dd>
        </div>

        @if ($cart->discountTotal > 0)
            <div class="storefront-cart-summary__row storefront-cart-summary__row--discount">
                <dt>{{ $cart->promotionName ?? __('storefront::storefront.discount') }}</dt>
                <dd>−<x-storefront.price :amount="$cart->discountTotal" :currency="$cart->currency" minor /></dd>
            </div>
        @endif
    </dl>

    <dl class="storefront-cart-summary__rows storefront-cart-summary__rows--totals">
        <div class="storefront-cart-summary__row">
            <dt>{{ __('storefront::storefront.shipping') }}</dt>
            <dd data-checkout-shipping-amount>
                @if ($shippingQuotes === [])
                    <span class="storefront-cart-summary__muted">{{ __('storefront::storefront.shipping_calculated') }}</span>
                @elseif ((int) $cheapestShipping === 0)
                    {{ __('storefront::storefront.shipping_free') }}
                @else
                    <x-storefront.price :amount="$cheapestShipping" :currency="$cart->currency" minor />
                @endif
            </dd>
        </div>

        <div class="storefront-cart-summary__row">
            <dt>{{ __('storefront::storefront.tax') }}</dt>
            <dd class="storefront-cart-summary__muted">{{ __('storefront::storefront.tax_at_checkout') }}</dd>
        </div>

        <div class="storefront-cart-summary__row storefront-cart-summary__row--grand">
            <dt>{{ __('storefront::storefront.grand_total') }}</dt>
            <dd data-cart-summary-total><x-storefront.price :amount="$estimatedTotal" :currency="$cart->currency" minor /></dd>
        </div>
    </dl>

    {{ $slot }}
</div>
