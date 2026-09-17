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
    $wrapperClass = $wrapperClass ?? 'storefront-location';
    $gridClass = $gridClass ?? 'storefront-form-grid';
    $fieldClass = $fieldClass ?? 'storefront-field';
    $labelClass = $labelClass ?? 'storefront-field__label';
    $selectClass = $selectClass ?? 'storefront-select';
    $inputClass = $inputClass ?? 'storefront-input';
    $stateKey = $stateKey ?? 'state';
    $fieldAttrs = $fieldAttrs ?? [];
    $extra = static function (string $field) use ($fieldAttrs): string {
        $html = '';
        foreach ($fieldAttrs[$field] ?? [] as $attribute => $attributeValue) {
            $html .= ' '.$attribute.'="'.e((string) $attributeValue).'"';
        }

        return $html;
    };
    $stateValue = $value($stateKey) !== '' ? $value($stateKey) : $value('state');
@endphp

<div @class([$wrapperClass]) data-thailand-address data-locations-url="{{ url('/api/v1/storefront/locations/thailand') }}">
    <div @class([$fieldClass])>
        <label class="{{ $labelClass }}" for="{{ $id('country_code') }}">{{ __('storefront::storefront.country') }}</label>
        <select
            id="{{ $id('country_code') }}"
            name="{{ $name('country_code') }}"
            class="{{ $selectClass }}"
            data-address-country
            data-address-field="country_code"
            data-address-prefix="{{ $prefix }}"
            @required($required)
        >
            <option value="TH" @selected($country === 'TH')>{{ __('storefront::storefront.country_th') }}</option>
            <option value="US" @selected($country === 'US')>{{ __('storefront::storefront.country_us') }}</option>
        </select>
    </div>

    <div data-location-thailand @class([$gridClass, 'storefront-is-hidden' => ! $isThailand])>
        <div @class([$fieldClass])>
            <label class="{{ $labelClass }}" for="{{ $id('province') }}">{{ __('storefront::storefront.province') }}</label>
            <select
                id="{{ $id('province') }}"
                class="{{ $selectClass }}"
                data-thailand-province
                data-selected="{{ $stateValue }}"
                @disabled(! $isThailand)
            >
                <option value="">{{ __('storefront::storefront.select_province') }}</option>
            </select>
            <input type="hidden" name="{{ $name($stateKey) }}" value="{{ $stateValue }}" data-address-field="state" data-address-prefix="{{ $prefix }}" data-thailand-state {!! $extra('state') !!} @disabled(! $isThailand)>
        </div>
        <div @class([$fieldClass])>
            <label class="{{ $labelClass }}" for="{{ $id('district') }}">{{ __('storefront::storefront.district') }}</label>
            <select
                id="{{ $id('district') }}"
                name="{{ $name('district') }}"
                class="{{ $selectClass }}"
                data-thailand-district
                data-address-field="district"
                data-address-prefix="{{ $prefix }}"
                data-selected="{{ $value('district') }}"
                {!! $extra('district') !!}
                @disabled(! $isThailand)
            >
                <option value="">{{ __('storefront::storefront.select_district') }}</option>
            </select>
        </div>
        <div @class([$fieldClass])>
            <label class="{{ $labelClass }}" for="{{ $id('subdistrict') }}">{{ __('storefront::storefront.subdistrict') }}</label>
            <select
                id="{{ $id('subdistrict') }}"
                name="{{ $name('subdistrict') }}"
                class="{{ $selectClass }}"
                data-thailand-subdistrict
                data-address-field="subdistrict"
                data-address-prefix="{{ $prefix }}"
                data-selected="{{ $value('subdistrict') }}"
                {!! $extra('subdistrict') !!}
                @disabled(! $isThailand)
            >
                <option value="">{{ __('storefront::storefront.select_subdistrict') }}</option>
            </select>
        </div>
    </div>

    <div data-location-international @class([$gridClass, 'storefront-is-hidden' => $isThailand])>
        <div @class([$fieldClass])>
            <label class="{{ $labelClass }}" for="{{ $id('city') }}">{{ __('storefront::storefront.city') }}</label>
            <input
                id="{{ $id('city') }}"
                name="{{ $name('city') }}"
                value="{{ $value('city') }}"
                class="{{ $inputClass }}"
                data-address-field="city"
                data-address-prefix="{{ $prefix }}"
                @required($required)
                @disabled($isThailand)
            >
        </div>
        <div @class([$fieldClass])>
            <label class="{{ $labelClass }}" for="{{ $id('state_free') }}">{{ __('storefront::storefront.state') }}</label>
            <input
                id="{{ $id('state_free') }}"
                name="{{ $name($stateKey) }}"
                value="{{ $stateValue }}"
                class="{{ $inputClass }}"
                data-address-field="state"
                data-address-prefix="{{ $prefix }}"
                data-location-state-free
                {!! $extra('state') !!}
                @disabled($isThailand)
            >
        </div>
    </div>

    <input type="hidden" name="{{ $name('city') }}" value="{{ $value('city') ?: $value('district') }}" data-thailand-city data-address-field="city" data-address-prefix="{{ $prefix }}" @disabled(! $isThailand)>

    <div @class([$fieldClass])>
        <label class="{{ $labelClass }}" for="{{ $id('postal_code') }}">{{ __('storefront::storefront.postal_code') }}</label>
        <input
            id="{{ $id('postal_code') }}"
            name="{{ $name('postal_code') }}"
            value="{{ $value('postal_code') }}"
            class="{{ $inputClass }}"
            data-address-field="postal_code"
            data-address-prefix="{{ $prefix }}"
            data-thailand-postal
            {!! $extra('postal_code') !!}
            @required($required)
        >
    </div>
</div>
