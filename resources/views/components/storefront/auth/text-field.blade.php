@props([
    'id',
    'label',
    'name',
    'value' => '',
    'type' => 'text',
    'required' => true,
    'autocomplete' => null,
    'inputmode' => null,
])

<div {{ $attributes->merge(['class' => 'storefront-auth-field']) }}>
    <label class="storefront-auth-field__label" for="{{ $id }}">{{ $label }}</label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value }}"
        @if ($required) required @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($inputmode) inputmode="{{ $inputmode }}" @endif
        class="storefront-auth-field__input cf-input"
    >
</div>
