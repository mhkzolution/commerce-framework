@props([
    'cart',
])

<div {{ $attributes->merge(['class' => 'storefront-coupon']) }}>
    <h2 class="storefront-coupon__title">{{ __('storefront::storefront.promotion_code') }}</h2>

    @if ($cart->couponCode)
        <div class="storefront-coupon__applied">
            <span class="storefront-coupon__code">
                {{ __('storefront::storefront.promotion_applied', ['code' => $cart->couponCode]) }}
            </span>
            @if ($cart->promotionName)
                <span class="storefront-coupon__name">{{ $cart->promotionName }}</span>
            @endif
            <form method="POST" action="{{ route('storefront.cart.coupon.remove') }}" class="storefront-coupon__remove">
                @csrf
                @method('DELETE')
                <button type="submit" class="storefront-coupon__remove-btn">{{ __('storefront::storefront.remove') }}</button>
            </form>
        </div>
    @else
        <form method="POST" action="{{ route('storefront.cart.coupon.apply') }}" class="storefront-coupon__form">
            @csrf
            <input
                name="code"
                type="text"
                placeholder="{{ __('storefront::storefront.promotion_code') }}"
                class="storefront-coupon__input cf-input"
                autocomplete="off"
                autocapitalize="characters"
            >
            <x-admin.button type="submit" variant="secondary" class="storefront-coupon__submit">
                {{ __('storefront::storefront.apply') }}
            </x-admin.button>
        </form>
    @endif

    @error('coupon')
        <p class="storefront-coupon__error">{{ $message }}</p>
    @enderror
</div>
