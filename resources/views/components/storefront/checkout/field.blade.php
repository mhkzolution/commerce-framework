@props([
    'id',
    'label',
    'name',
    'value' => '',
    'type' => 'text',
    'required' => false,
    'autocomplete' => null,
    'inputmode' => null,
    'placeholder' => null,
])

<div {{ $attributes->merge(['class' => 'storefront-checkout-field']) }}>
    <label class="storefront-checkout-field__label" for="{{ $id }}">{{ $label }}</label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value }}"
        @if ($required) required @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($inputmode) inputmode="{{ $inputmode }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        class="storefront-checkout-field__input cf-input"
    >
</div>
