@props([
    'variant' => 'default',
])

@php
    $classes = collect([
        'storefront-page-container',
        $variant === 'narrow' ? 'storefront-page-container--narrow' : null,
    ])->filter()->implode(' ');
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
