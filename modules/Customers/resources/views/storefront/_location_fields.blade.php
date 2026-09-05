@php
    $prefix = $prefix ?? '';
    $prefill = $prefill ?? [];
    $address = $address ?? null;
    $required = $required ?? true;
    $dot = $prefix === '' ? '' : $prefix.'.';
    $name = static fn (string $field): string => $prefix === '' ? $field : $prefix.'['.$field.']';
    $id = static fn (string $field): string => $prefix === '' ? $field : $prefix.'_'.$field;
    $value = static function (string $field, mixed $default = '') use ($dot, $prefill, $address): string {
        $fallback = $prefill[$field] ?? ($address->{$field} ?? $default);

        return (string) old($dot.$field, $fallback ?? '');
    };
    $country = strtoupper($value('country_code', 'TH') ?: 'TH');
    $isThailand = $country === 'TH';
@endphp

<div class="storefront-location" data-thailand-address data-locations-url="{{ url('/api/v1/storefront/locations/thailand') }}">
    <div class="storefront-field">
        <label class="storefront-field__label" for="{{ $id('country_code') }}">{{ __('storefront::storefront.country') }}</label>
        <select
            id="{{ $id('country_code') }}"
            name="{{ $name('country_code') }}"
            class="storefront-select"
            data-address-country
            data-address-field="country_code"
            data-address-prefix="{{ $prefix }}"
            @required($required)
        >
            <option value="TH" @selected($country === 'TH')>{{ __('storefront::storefront.country_th') }}</option>
            <option value="US" @selected($country === 'US')>{{ __('storefront::storefront.country_us') }}</option>
        </select>
    </div>

    <div class="storefront-form-grid" data-location-thailand @class(['storefront-is-hidden' => ! $isThailand])>
        <div class="storefront-field">
            <label class="storefront-field__label" for="{{ $id('province') }}">{{ __('storefront::storefront.province') }}</label>
            <select
                id="{{ $id('province') }}"
                class="storefront-select"
                data-thailand-province
                data-selected="{{ $value('state') }}"
                @disabled(! $isThailand)
            >
                <option value="">{{ __('storefront::storefront.select_province') }}</option>
            </select>
            <input type="hidden" name="{{ $name('state') }}" value="{{ $value('state') }}" data-address-field="state" data-address-prefix="{{ $prefix }}" data-thailand-state @disabled(! $isThailand)>
        </div>
        <div class="storefront-field">
            <label class="storefront-field__label" for="{{ $id('district') }}">{{ __('storefront::storefront.district') }}</label>
            <select
                id="{{ $id('district') }}"
                name="{{ $name('district') }}"
                class="storefront-select"
                data-thailand-district
                data-address-field="district"
                data-address-prefix="{{ $prefix }}"
                data-selected="{{ $value('district') }}"
                @disabled(! $isThailand)
            >
                <option value="">{{ __('storefront::storefront.select_district') }}</option>
            </select>
        </div>
        <div class="storefront-field">
            <label class="storefront-field__label" for="{{ $id('subdistrict') }}">{{ __('storefront::storefront.subdistrict') }}</label>
            <select
                id="{{ $id('subdistrict') }}"
                name="{{ $name('subdistrict') }}"
                class="storefront-select"
                data-thailand-subdistrict
                data-address-field="subdistrict"
                data-address-prefix="{{ $prefix }}"
                data-selected="{{ $value('subdistrict') }}"
                @disabled(! $isThailand)
            >
                <option value="">{{ __('storefront::storefront.select_subdistrict') }}</option>
            </select>
        </div>
    </div>

    <div class="storefront-form-grid" data-location-international @class(['storefront-is-hidden' => $isThailand])>
        <div class="storefront-field">
            <label class="storefront-field__label" for="{{ $id('city') }}">{{ __('storefront::storefront.city') }}</label>
            <input
                id="{{ $id('city') }}"
                name="{{ $name('city') }}"
                value="{{ $value('city') }}"
                class="storefront-input"
                data-address-field="city"
                data-address-prefix="{{ $prefix }}"
                @required($required)
                @disabled($isThailand)
            >
        </div>
        <div class="storefront-field">
            <label class="storefront-field__label" for="{{ $id('state_free') }}">{{ __('storefront::storefront.state') }}</label>
            <input
                id="{{ $id('state_free') }}"
                name="{{ $name('state') }}"
                value="{{ $value('state') }}"
                class="storefront-input"
                data-address-field="state"
                data-address-prefix="{{ $prefix }}"
                data-location-state-free
                @disabled($isThailand)
            >
        </div>
    </div>

    <input type="hidden" name="{{ $name('city') }}" value="{{ $value('city') ?: $value('district') }}" data-thailand-city data-address-field="city" data-address-prefix="{{ $prefix }}" @disabled(! $isThailand)>

    <div class="storefront-field">
        <label class="storefront-field__label" for="{{ $id('postal_code') }}">{{ __('storefront::storefront.postal_code') }}</label>
        <input
            id="{{ $id('postal_code') }}"
            name="{{ $name('postal_code') }}"
            value="{{ $value('postal_code') }}"
            class="storefront-input"
            data-address-field="postal_code"
            data-address-prefix="{{ $prefix }}"
            data-thailand-postal
            @required($required)
        >
    </div>
</div>
