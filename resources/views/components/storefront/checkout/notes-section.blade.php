<x-storefront.checkout.section
    :title="__('storefront::storefront.checkout_order_notes')"
    step="5"
>
    <div class="storefront-checkout-field storefront-checkout-field--full">
        <label class="sr-only" for="order_notes">{{ __('storefront::storefront.checkout_order_notes') }}</label>
        <textarea
            id="order_notes"
            name="order_notes"
            rows="3"
            class="storefront-checkout-field__input storefront-checkout-field__textarea cf-input"
            placeholder="{{ __('storefront::storefront.checkout_order_notes_placeholder') }}"
        >{{ old('order_notes') }}</textarea>
    </div>
</x-storefront.checkout.section>
