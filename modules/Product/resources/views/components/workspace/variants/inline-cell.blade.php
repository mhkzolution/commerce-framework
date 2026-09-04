@props([
    'type' => 'text',
    'name' => '',
    'placeholder' => '',
    'step' => null,
    'min' => null,
    'value' => '',
])

<input
    type="{{ $type }}"
    class="cf-variant-inline-cell"
    data-variant-field="{{ $name }}"
    placeholder="{{ $placeholder }}"
    value="{{ $value }}"
    @if ($step) step="{{ $step }}" @endif
    @if ($min !== null) min="{{ $min }}" @endif
>
