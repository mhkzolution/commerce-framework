@props([
    'autofocus' => true,
])

<div class="scanner-input-zone">
    <label for="warehouse-scanner-input" class="scanner-input-zone__label">{{ __('warehouse::scanner.sku') }}</label>
    <input
        type="text"
        id="warehouse-scanner-input"
        class="scanner-input"
        placeholder="{{ __('warehouse::scanner.scan_placeholder') }}"
        autocomplete="off"
        inputmode="text"
        data-scanner-input
        @if ($autofocus) autofocus @endif
    >
    <p class="scanner-input-zone__hint">
        {{ __('warehouse::scanner.scan_hint', ['shortcut' => 'Ctrl+B']) }}
    </p>
</div>
