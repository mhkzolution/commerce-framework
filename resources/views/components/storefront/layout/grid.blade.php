{{--
Temporary adapter for Blog UI Refresh (v1.3.0)

This component intentionally does not depend on
commerce-framework-v1 storefront primitives.

Replace with shared storefront primitives
when the design system is merged.
--}}
@props([
    'variant' => 'blog',
    'columns' => null,
])

@php
    $classes = collect([
        'storefront-grid',
        'storefront-grid--'.$variant,
        $variant === 'blog' ? 'storefront-article-grid' : null,
        $columns ? 'storefront-grid--cols-'.$columns : null,
    ])->filter()->implode(' ');
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
