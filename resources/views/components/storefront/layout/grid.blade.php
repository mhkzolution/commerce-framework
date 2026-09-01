@props([
    'variant' => 'product',
    'columns' => null,
])

@php
    $classes = collect([
        'storefront-grid',
        'storefront-grid--' . $variant,
        $variant === 'product' ? 'storefront-product-grid storefront-product-grid--shop' : null,
        $variant === 'blog' ? 'storefront-article-grid' : null,
        $variant === 'brand' ? 'storefront-brand-grid' : null,
        $columns ? 'storefront-grid--cols-' . $columns : null,
    ])->filter()->implode(' ');
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
