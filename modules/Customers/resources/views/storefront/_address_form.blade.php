<div class="storefront-form-grid">
    <div class="storefront-field">
        <label class="storefront-field__label" for="label">{{ __('storefront::storefront.label') }}</label>
        <input id="label" name="label" value="{{ old('label') }}" class="storefront-input">
    </div>
    <div class="storefront-field">
        <label class="storefront-field__label" for="type">{{ __('storefront::storefront.type') }}</label>
        <select id="type" name="type" class="storefront-select">
            <option value="both" @selected(old('type', 'both') === 'both')>{{ __('storefront::storefront.type_both') }}</option>
            <option value="shipping" @selected(old('type') === 'shipping')>{{ __('storefront::storefront.type_shipping') }}</option>
            <option value="billing" @selected(old('type') === 'billing')>{{ __('storefront::storefront.type_billing') }}</option>
        </select>
    </div>
    <div class="storefront-field storefront-form-grid__full">
        <label class="storefront-field__label" for="line1">{{ __('storefront::storefront.address_line1') }}</label>
        <input id="line1" name="line1" value="{{ old('line1') }}" required class="storefront-input">
    </div>
    <div class="storefront-field storefront-form-grid__full">
        <label class="storefront-field__label" for="line2">{{ __('storefront::storefront.address_line2') }}</label>
        <input id="line2" name="line2" value="{{ old('line2') }}" class="storefront-input">
    </div>
    <div class="storefront-field">
        <label class="storefront-field__label" for="city">{{ __('storefront::storefront.city') }}</label>
        <input id="city" name="city" value="{{ old('city') }}" required class="storefront-input">
    </div>
    <div class="storefront-field">
        <label class="storefront-field__label" for="state">{{ __('storefront::storefront.state') }}</label>
        <input id="state" name="state" value="{{ old('state') }}" class="storefront-input">
    </div>
    <div class="storefront-field">
        <label class="storefront-field__label" for="postal_code">{{ __('storefront::storefront.postal_code') }}</label>
        <input id="postal_code" name="postal_code" value="{{ old('postal_code') }}" required class="storefront-input">
    </div>
    <div class="storefront-field">
        <label class="storefront-field__label" for="country_code">{{ __('storefront::storefront.country') }}</label>
        <input id="country_code" name="country_code" value="{{ old('country_code', 'US') }}" maxlength="2" required class="storefront-input">
    </div>
    <div class="storefront-form-grid__full">
        <label class="storefront-check">
            <input type="checkbox" name="is_default" value="1">
            {{ __('storefront::storefront.set_default') }}
        </label>
    </div>
</div>
