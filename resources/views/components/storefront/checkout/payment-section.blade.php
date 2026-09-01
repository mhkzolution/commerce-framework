@props([
    'methods' => null,
])

@php
    $methods ??= config('cart.storefront.checkout_payment_methods', []);
    $labelKey = static fn (string $id): string => match ($id) {
        'card' => 'storefront::storefront.checkout_payment_card',
        'promptpay' => 'storefront::storefront.checkout_payment_promptpay',
        'apple_pay' => 'storefront::storefront.checkout_payment_apple_pay',
        'google_pay' => 'storefront::storefront.checkout_payment_google_pay',
        'cod' => 'storefront::storefront.checkout_payment_cod',
        'store_credit' => 'storefront::storefront.checkout_payment_store_credit',
        'gift_card' => 'storefront::storefront.checkout_payment_gift_card',
        default => 'storefront::storefront.checkout_payment_card',
    };
    $defaultMethod = old('payment_method', $methods[0]['id'] ?? 'card');
@endphp

<x-storefront.checkout.section
    :title="__('storefront::storefront.checkout_payment_method')"
    step="4"
>
    <fieldset class="storefront-checkout-payment">
        <legend class="sr-only">{{ __('storefront::storefront.checkout_payment_method') }}</legend>
        <div class="storefront-checkout-payment__list">
            @foreach ($methods as $method)
                <label class="storefront-checkout-option storefront-checkout-option--payment">
                    <input
                        type="radio"
                        name="payment_method"
                        value="{{ $method['id'] }}"
                        class="storefront-checkout-option__input"
                        @checked($defaultMethod === $method['id'])
                    >
                    <span class="storefront-checkout-option__body">
                        <span class="storefront-checkout-option__icon storefront-checkout-option__icon--{{ $method['icon'] ?? $method['id'] }}" aria-hidden="true"></span>
                        <span class="storefront-checkout-option__main">
                            <span class="storefront-checkout-option__title">{{ __($labelKey($method['id'])) }}</span>
                        </span>
                    </span>
                </label>
            @endforeach
        </div>
    </fieldset>
</x-storefront.checkout.section>
