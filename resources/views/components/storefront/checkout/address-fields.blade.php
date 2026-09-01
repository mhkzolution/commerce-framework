@props([
    'prefix' => 'shipping_address',
])

<div class="storefront-checkout-address-fields" data-checkout-address-fields data-address-prefix="{{ $prefix }}">
    <div class="storefront-checkout-field-grid">
        <x-storefront.checkout.field
            :id="$prefix . '_line1'"
            :name="$prefix . '[line1]'"
            :label="__('storefront::storefront.checkout_address_line1')"
            :value="old($prefix.'.line1')"
            autocomplete="address-line1"
            :required="true"
            class="storefront-checkout-field--full"
        />

        <x-storefront.checkout.field
            :id="$prefix . '_line2'"
            :name="$prefix . '[line2]'"
            :label="__('storefront::storefront.checkout_apartment')"
            :value="old($prefix.'.line2')"
            autocomplete="address-line2"
            :required="false"
            class="storefront-checkout-field--full"
        />

        <div class="storefront-checkout-field storefront-checkout-field--half">
            <label class="storefront-checkout-field__label" for="{{ $prefix }}_country_code">{{ __('storefront::storefront.checkout_country') }}</label>
            <input
                id="{{ $prefix }}_country_code"
                name="{{ $prefix }}[country_code]"
                value="{{ old($prefix.'.country_code', 'TH') }}"
                maxlength="2"
                required
                autocomplete="country"
                class="storefront-checkout-field__input cf-input uppercase"
            >
        </div>

        <x-storefront.checkout.field
            :id="$prefix . '_state'"
            :name="$prefix . '[state]'"
            :label="__('storefront::storefront.checkout_state')"
            :value="old($prefix.'.state')"
            autocomplete="address-level1"
            :required="false"
        />

        <x-storefront.checkout.field
            :id="$prefix . '_district'"
            :name="$prefix . '_district'"
            :label="__('storefront::storefront.checkout_district')"
            :value="old($prefix.'_district')"
            data-address-district
            :required="false"
        />

        <x-storefront.checkout.field
            :id="$prefix . '_subdistrict'"
            :name="$prefix . '_subdistrict'"
            :label="__('storefront::storefront.checkout_subdistrict')"
            :value="old($prefix.'_subdistrict', old($prefix.'.city'))"
            data-address-subdistrict
            autocomplete="address-level2"
            :required="true"
        />

        <input type="hidden" :name="$prefix . '[city]'" value="{{ old($prefix.'.city') }}" data-address-city>

        <x-storefront.checkout.field
            :id="$prefix . '_postal_code'"
            :name="$prefix . '[postal_code]'"
            :label="__('storefront::storefront.checkout_postal_code')"
            :value="old($prefix.'.postal_code')"
            autocomplete="postal-code"
            inputmode="numeric"
            :required="true"
        />
    </div>
</div>
