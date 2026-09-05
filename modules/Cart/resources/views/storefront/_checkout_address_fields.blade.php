@php
    $required = $required ?? true;
    $prefill = $prefill ?? [];
    $recipientName = old($prefix.'.recipient_name', $prefill['recipient_name'] ?? '');
    $recipientPhone = old($prefix.'.phone', $prefill['phone'] ?? '');
@endphp

<fieldset class="storefront-stack" data-checkout-address="{{ $prefix }}">
    <legend class="storefront-visually-hidden">{{ $legend }}</legend>
    <div class="storefront-form-grid">
        <div class="storefront-field">
            <label class="storefront-field__label" for="{{ $prefix }}_recipient_name">{{ __('storefront::storefront.recipient_name') }}</label>
            <input
                id="{{ $prefix }}_recipient_name"
                name="{{ $prefix }}[recipient_name]"
                value="{{ $recipientName }}"
                autocomplete="name"
                @required($required)
                class="storefront-input"
                data-address-field="recipient_name"
                data-address-prefix="{{ $prefix }}"
            >
        </div>
        <div class="storefront-field">
            <label class="storefront-field__label" for="{{ $prefix }}_phone">{{ __('storefront::storefront.phone') }}</label>
            <input
                id="{{ $prefix }}_phone"
                type="tel"
                name="{{ $prefix }}[phone]"
                value="{{ $recipientPhone }}"
                autocomplete="tel"
                inputmode="tel"
                @required($required)
                class="storefront-input"
                data-address-field="phone"
                data-address-prefix="{{ $prefix }}"
            >
        </div>
        <div class="storefront-field storefront-form-grid__full">
            <label class="storefront-field__label" for="{{ $prefix }}_line1">
                <span data-label-th>{{ __('storefront::storefront.address_house_street') }}</span>
                <span data-label-intl class="storefront-is-hidden">{{ __('storefront::storefront.street_address') }}</span>
            </label>
            <input
                id="{{ $prefix }}_line1"
                name="{{ $prefix }}[line1]"
                value="{{ old($prefix.'.line1', $prefill['line1'] ?? '') }}"
                autocomplete="address-line1"
                @required($required)
                class="storefront-input"
                data-address-field="line1"
                data-address-prefix="{{ $prefix }}"
            >
        </div>
        <div class="storefront-field storefront-form-grid__full">
            <label class="storefront-field__label" for="{{ $prefix }}_line2">
                <span data-label-th>{{ __('storefront::storefront.address_landmark') }}</span>
                <span data-label-intl class="storefront-is-hidden">{{ __('storefront::storefront.address_apt') }}</span>
            </label>
            <input
                id="{{ $prefix }}_line2"
                name="{{ $prefix }}[line2]"
                value="{{ old($prefix.'.line2', $prefill['line2'] ?? '') }}"
                autocomplete="address-line2"
                class="storefront-input"
                data-address-field="line2"
                data-address-prefix="{{ $prefix }}"
            >
        </div>
    </div>
    @include('customers::storefront._location_fields', [
        'prefix' => $prefix,
        'prefill' => $prefill,
        'required' => $required,
    ])
</fieldset>
