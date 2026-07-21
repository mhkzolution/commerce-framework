@props([
    'label',
    'value',
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-border bg-card p-5 shadow-sm']) }}>
    <p class="text-sm text-muted">{{ $label }}</p>
    <p class="mt-2 text-2xl font-semibold tracking-tight text-text">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-muted">{{ $hint }}</p>
    @endif
    @isset($footer)
        <div class="mt-1 text-xs text-muted">{{ $footer }}</div>
    @endisset
</div>
