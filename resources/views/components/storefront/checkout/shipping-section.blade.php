@props([
    'shippingQuotes' => [],
    'currency' => 'THB',
])

@if ($shippingQuotes !== [])
    <x-storefront.checkout.section
        :title="__('storefront::storefront.checkout_shipping_method')"
        step="3"
    >
        <fieldset class="storefront-checkout-shipping">
            <legend class="sr-only">{{ __('storefront::storefront.checkout_shipping_method') }}</legend>
            <div class="storefront-checkout-shipping__list">
                @foreach ($shippingQuotes as $index => $quote)
                    <label class="storefront-checkout-option">
                        <input
                            type="radio"
                            name="shipping_method_uuid"
                            value="{{ $quote->uuid }}"
                            class="storefront-checkout-option__input shipping-method-input"
                            data-price="{{ $quote->price }}"
                            data-checkout-shipping-method
                            @checked(old('shipping_method_uuid', $shippingQuotes[0]->uuid ?? null) === $quote->uuid)
                            @required($index === 0)
                        >
                        <span class="storefront-checkout-option__body">
                            <span class="storefront-checkout-option__main">
                                <span class="storefront-checkout-option__title">{{ $quote->name }}</span>
                                @if ($quote->description)
                                    <span class="storefront-checkout-option__meta">{{ $quote->description }}</span>
                                @endif
                            </span>
                            <span class="storefront-checkout-option__price">
                                @if ($quote->price === 0)
                                    {{ __('storefront::storefront.shipping_free') }}
                                @else
                                    <x-storefront.price :amount="$quote->price" :currency="$currency" minor />
                                @endif
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    </x-storefront.checkout.section>
@endif
