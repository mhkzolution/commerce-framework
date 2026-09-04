<fieldset>
    <legend class="storefront-field__label">{{ $legend }}</legend>
    <div class="storefront-form-grid">
        <div class="storefront-field storefront-form-grid__full">
            <label class="storefront-field__label" for="{{ $prefix }}_line1">{{ __('storefront::storefront.address_line1') }}</label>
            <input id="{{ $prefix }}_line1" name="{{ $prefix }}[line1]" value="{{ old($prefix.'.line1') }}" required class="storefront-input">
        </div>
        <div class="storefront-field storefront-form-grid__full">
            <label class="storefront-field__label" for="{{ $prefix }}_line2">{{ __('storefront::storefront.address_line2') }}</label>
            <input id="{{ $prefix }}_line2" name="{{ $prefix }}[line2]" value="{{ old($prefix.'.line2') }}" class="storefront-input">
        </div>
        <div class="storefront-field">
            <label class="storefront-field__label" for="{{ $prefix }}_city">{{ __('storefront::storefront.city') }}</label>
            <input id="{{ $prefix }}_city" name="{{ $prefix }}[city]" value="{{ old($prefix.'.city') }}" required class="storefront-input">
        </div>
        <div class="storefront-field">
            <label class="storefront-field__label" for="{{ $prefix }}_state">{{ __('storefront::storefront.state') }}</label>
            <input id="{{ $prefix }}_state" name="{{ $prefix }}[state]" value="{{ old($prefix.'.state') }}" class="storefront-input">
        </div>
        <div class="storefront-field">
            <label class="storefront-field__label" for="{{ $prefix }}_postal_code">{{ __('storefront::storefront.postal_code') }}</label>
            <input id="{{ $prefix }}_postal_code" name="{{ $prefix }}[postal_code]" value="{{ old($prefix.'.postal_code') }}" required class="storefront-input">
        </div>
        <div class="storefront-field">
            <label class="storefront-field__label" for="{{ $prefix }}_country_code">{{ __('storefront::storefront.country') }}</label>
            <input id="{{ $prefix }}_country_code" name="{{ $prefix }}[country_code]" value="{{ old($prefix.'.country_code', 'US') }}" maxlength="2" required class="storefront-input">
        </div>
    </div>
</fieldset>
