@props([
    'width' => 'default',
])

@php
    $widthClass = match ($width) {
        'narrow' => 'storefront-page-container--narrow',
        'reading' => 'storefront-page-container--reading',
        'wide' => 'storefront-page-container--wide',
        default => '',
    };
@endphp

<div {{ $attributes->merge(['class' => 'storefront-page-container ' . $widthClass]) }}>
    {{ $slot }}
</div>
