@php
    $role = $role ?? 'shipping';
    $prefix = $prefix ?? ($role === 'billing' ? 'billing_address' : 'shipping_address');
    $saveName = $role === 'billing' ? 'save_billing_address' : 'save_shipping_address';
    $labelName = $role === 'billing' ? 'billing_address_label' : 'shipping_address_label';
    $open = $open ?? false;
    $required = $required ?? false;
@endphp

<div
    class="storefront-address-editor"
    data-address-editor="{{ $role }}"
    @if (! $open) hidden @endif
>
    @if ($dismissable ?? true)
        <div class="storefront-address-editor__header">
            <h3
                class="storefront-address-editor__title"
                data-title-add="{{ __('storefront::storefront.add_new_address') }}"
                data-title-edit="{{ __('storefront::storefront.edit_address') }}"
            >{{ __('storefront::storefront.add_new_address') }}</h3>
            <button type="button" class="storefront-btn storefront-btn--ghost" data-cancel-address="{{ $role }}">
                {{ __('storefront::storefront.cancel') }}
            </button>
        </div>
    @endif

    @include('cart::storefront._checkout_address_fields', [
        'prefix' => $prefix,
        'legend' => $legend,
        'prefill' => $prefill ?? [],
        'required' => $required,
    ])

    @if ($customer ?? null)
        <fieldset class="storefront-stack">
            <legend class="storefront-field__label">{{ __('storefront::storefront.address_save_choice') }}</legend>
            <label class="storefront-check">
                <input type="radio" name="{{ $saveName }}" value="0" @checked(! old($saveName))>
                {{ __('storefront::storefront.use_once') }}
            </label>
            <label class="storefront-check">
                <input type="radio" name="{{ $saveName }}" value="1" @checked(old($saveName))>
                {{ __('storefront::storefront.save_to_address_book') }}
            </label>
            <div class="storefront-field">
                <label class="storefront-field__label" for="{{ $labelName }}">{{ __('storefront::storefront.label') }}</label>
                <input id="{{ $labelName }}" name="{{ $labelName }}" value="{{ old($labelName) }}" class="storefront-input" placeholder="{{ __('storefront::storefront.address_label_placeholder') }}">
            </div>
        </fieldset>
    @endif
</div>
