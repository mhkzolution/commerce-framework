@props([
    'cart',
    'page',
])

{{-- Mobile: fixed Shopee-style checkout bar --}}
<div class="storefront-cart-dock" data-cart-dock>
    <button type="button" class="storefront-cart-dock__promo-row" data-cart-coupon-toggle aria-expanded="false">
        <span class="storefront-cart-dock__promo-icon storefront-cart-dock__promo-icon--coupon" aria-hidden="true"></span>
        <span class="storefront-cart-dock__promo-label">
            @if ($cart->couponCode)
                {{ __('storefront::storefront.promotion_applied', ['code' => $cart->couponCode]) }}
            @else
                {{ __('storefront::storefront.promotion_code') }}
            @endif
        </span>
        <span class="storefront-cart-dock__promo-action">
            {{ __('storefront::storefront.apply_coupon_cta') }}
            <span aria-hidden="true">›</span>
        </span>
    </button>

    <div class="storefront-cart-dock__coupon-panel" data-cart-coupon-panel @if (! $cart->couponCode) hidden @endif>
        <x-storefront.cart.coupon-form :cart="$cart" />
    </div>

    @if ($page->freeShipping)
        <div class="storefront-cart-dock__promo-row storefront-cart-dock__promo-row--static">
            <span class="storefront-cart-dock__promo-icon storefront-cart-dock__promo-icon--shipping" aria-hidden="true"></span>
            <span class="storefront-cart-dock__promo-label">
                @if ($page->freeShipping['qualified'])
                    {{ __('storefront::storefront.free_shipping_qualified') }}
                @else
                    {{ __('storefront::storefront.free_shipping_progress', ['amount' => \Commerce\Cart\Support\StorefrontMoney::formatMajor($page->freeShipping['remaining'] / 100, (string) $cart->currency, 0)]) }}
                @endif
            </span>
        </div>
    @endif

    <form method="POST" action="{{ route('storefront.cart.checkout.prepare') }}" class="storefront-cart-dock__action-bar" data-cart-checkout-form>
        @csrf
        <div data-cart-checkout-items hidden></div>

        <label class="storefront-cart-dock__select-all">
            <input type="checkbox" data-cart-select-all checked>
            <span>{{ __('storefront::storefront.select_all') }}</span>
        </label>

        <div class="storefront-cart-dock__total">
            <span class="storefront-cart-dock__total-label">{{ __('storefront::storefront.total_short') }}</span>
            <span class="storefront-cart-dock__total-amount" data-cart-dock-total>
                <x-storefront.price :amount="$page->estimatedTotal" :currency="$cart->currency" minor />
            </span>
        </div>

        <button type="submit" class="storefront-cart-dock__checkout-btn" data-cart-checkout-button>
            {{ __('storefront::storefront.place_order') }}
        </button>
    </form>
</div>

{{-- Desktop: sticky sidebar --}}
<aside {{ $attributes->merge(['class' => 'storefront-cart-sidebar']) }}>
    <x-storefront.cart.free-shipping-progress
        :progress="$page->freeShipping"
        :currency="$cart->currency"
    />

    <x-storefront.cart.summary-totals
        :cart="$cart"
        :estimated-total="$page->estimatedTotal"
        :cheapest-shipping="$page->cheapestShipping"
        :shipping-quotes="$page->shippingQuotes"
    >
        <x-storefront.cart.coupon-form :cart="$cart" class="storefront-cart-summary__coupon" />

        @if ($page->estimatedDelivery)
            <p class="storefront-cart-sidebar__delivery">
                <span class="storefront-cart-sidebar__delivery-label">{{ __('storefront::storefront.estimated_delivery') }}</span>
                {{ $page->estimatedDelivery }}
            </p>
        @endif

        <p class="storefront-cart-summary__secure">
            <span class="storefront-cart-summary__secure-icon" aria-hidden="true"></span>
            {{ __('storefront::storefront.secure_checkout') }}
        </p>

        <form method="POST" action="{{ route('storefront.cart.checkout.prepare') }}" data-cart-checkout-form>
            @csrf
            <div data-cart-checkout-items hidden></div>

            <button type="submit" class="storefront-cart-checkout-btn cf-btn cf-btn--primary" data-cart-checkout-button>
                {{ __('storefront::storefront.checkout') }}
                <span class="storefront-cart-checkout-btn__count" data-cart-selected-count></span>
            </button>
        </form>

        <x-storefront.cart.payment-icons class="storefront-cart-summary__payments" />
        <x-storefront.cart.trust-indicators />
    </x-storefront.cart.summary-totals>
</aside>
