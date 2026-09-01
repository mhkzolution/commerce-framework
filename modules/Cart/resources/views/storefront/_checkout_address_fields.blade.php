@props(['prefix' => 'shipping_address', 'legend' => null])

@php
    $legend ??= __('storefront::storefront.checkout_shipping_address');
@endphp

<fieldset>
    <legend class="text-sm font-medium text-text">{{ $legend }}</legend>
    <div class="mt-2 grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-text" for="{{ $prefix }}_line1">{{ __('storefront::storefront.checkout_address_line1') }}</label>
            <input id="{{ $prefix }}_line1" name="{{ $prefix }}[line1]" value="{{ old($prefix.'.line1') }}" required class="cf-input mt-1">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-text" for="{{ $prefix }}_line2">{{ __('storefront::storefront.checkout_address_line2') }}</label>
            <input id="{{ $prefix }}_line2" name="{{ $prefix }}[line2]" value="{{ old($prefix.'.line2') }}" class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="{{ $prefix }}_city">{{ __('storefront::storefront.checkout_city') }}</label>
            <input id="{{ $prefix }}_city" name="{{ $prefix }}[city]" value="{{ old($prefix.'.city') }}" required class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="{{ $prefix }}_state">{{ __('storefront::storefront.checkout_state') }}</label>
            <input id="{{ $prefix }}_state" name="{{ $prefix }}[state]" value="{{ old($prefix.'.state') }}" class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="{{ $prefix }}_postal_code">{{ __('storefront::storefront.checkout_postal_code') }}</label>
            <input id="{{ $prefix }}_postal_code" name="{{ $prefix }}[postal_code]" value="{{ old($prefix.'.postal_code') }}" required class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="{{ $prefix }}_country_code">{{ __('storefront::storefront.checkout_country') }}</label>
            <input id="{{ $prefix }}_country_code" name="{{ $prefix }}[country_code]" value="{{ old($prefix.'.country_code', 'TH') }}" maxlength="2" required class="cf-input mt-1 uppercase">
        </div>
    </div>
</fieldset>
