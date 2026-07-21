@props([
    'variant' => 'secondary',
    'href' => null,
    'type' => 'button',
])

@php
    $variantClass = match ($variant) {
        'primary' => 'cf-btn--primary',
        'secondary' => 'cf-btn--secondary',
        'ghost' => 'cf-btn--ghost',
        'outline' => 'cf-btn--outline',
        'success' => 'cf-btn--success',
        'danger' => 'cf-btn--danger',
        'warning' => 'cf-btn--warning',
        'link' => 'cf-btn--link',
        default => 'cf-btn--secondary',
    };
    $class = 'cf-btn ' . $variantClass;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif
