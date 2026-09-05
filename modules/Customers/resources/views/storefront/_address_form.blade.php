<div class="storefront-form-grid">
    <div class="storefront-field">
        <label class="storefront-field__label" for="label">{{ __('storefront::storefront.label') }}</label>
        <input id="label" name="label" value="{{ old('label', $address->label ?? '') }}" class="storefront-input">
    </div>
    <div class="storefront-field">
        <label class="storefront-field__label" for="type">{{ __('storefront::storefront.type') }}</label>
        <select id="type" name="type" class="storefront-select">
            <option value="both" @selected(old('type', $address->type ?? 'both') === 'both')>{{ __('storefront::storefront.type_both') }}</option>
            <option value="shipping" @selected(old('type', $address->type ?? '') === 'shipping')>{{ __('storefront::storefront.type_shipping') }}</option>
            <option value="billing" @selected(old('type', $address->type ?? '') === 'billing')>{{ __('storefront::storefront.type_billing') }}</option>
        </select>
    </div>
    <div class="storefront-field storefront-form-grid__full">
        <label class="storefront-field__label" for="line1">{{ __('storefront::storefront.address_line1') }}</label>
        <input id="line1" name="line1" value="{{ old('line1', $address->line1 ?? '') }}" required class="storefront-input">
    </div>
    <div class="storefront-field storefront-form-grid__full">
        <label class="storefront-field__label" for="line2">{{ __('storefront::storefront.address_line2') }}</label>
        <input id="line2" name="line2" value="{{ old('line2', $address->line2 ?? '') }}" class="storefront-input">
    </div>
</div>
@include('customers::storefront._location_fields', [
    'prefix' => '',
    'address' => $address ?? null,
    'required' => true,
])
<div class="storefront-form-grid">
    <div class="storefront-form-grid__full storefront-stack">
        <label class="storefront-check">
            <input type="checkbox" name="is_default_shipping" value="1" @checked(old('is_default_shipping', $address->is_default_shipping ?? false))>
            {{ __('storefront::storefront.set_default_shipping') }}
        </label>
        <label class="storefront-check">
            <input type="checkbox" name="is_default_billing" value="1" @checked(old('is_default_billing', $address->is_default_billing ?? false))>
            {{ __('storefront::storefront.set_default_billing') }}
        </label>
    </div>
</div>
