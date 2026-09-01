@props([
    'id',
    'label' => null,
    'open' => false,
])

<div
    {{ $attributes->merge(['class' => 'storefront-drawer']) }}
    data-drawer="{{ $id }}"
    @if (! $open) hidden @endif
>
    <div class="storefront-drawer__backdrop" data-drawer-close="{{ $id }}"></div>
    <div class="storefront-drawer__panel" role="dialog" aria-modal="true" @if ($label) aria-label="{{ $label }}" @endif>
        @if (isset($header))
            <div class="storefront-drawer__header">{{ $header }}</div>
        @endif
        <div class="storefront-drawer__body">{{ $slot }}</div>
        @if (isset($footer))
            <div class="storefront-drawer__footer">{{ $footer }}</div>
        @endif
    </div>
</div>
