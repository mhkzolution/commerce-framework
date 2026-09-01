@props([
    'action' => '',
    'label' => '',
    'hotkey' => null,
    'variant' => 'secondary',
    'disabled' => false,
    'dataAction' => null,
])

@php
    $dataAction = $dataAction ?? $action;
@endphp

<button
    type="button"
    class="scanner-quick-action scanner-quick-action--{{ $variant }}"
    data-scanner-action="{{ $dataAction }}"
    @if ($hotkey) data-scanner-hotkey="{{ $hotkey }}" @endif
    @disabled($disabled)
>
    @if ($hotkey)
        <span class="scanner-quick-action__key"><kbd>{{ $hotkey }}</kbd></span>
    @endif
    <span class="scanner-quick-action__label">{{ $label }}</span>
</button>
