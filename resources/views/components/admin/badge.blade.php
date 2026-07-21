@props(['variant' => 'default'])

@php
    $variantClass = match ($variant) {
        'success' => 'cf-badge--success',
        'warning' => 'cf-badge--warning',
        'danger' => 'cf-badge--danger',
        'info' => 'cf-badge--info',
        'draft' => 'cf-badge--draft',
        'pending' => 'cf-badge--pending',
        'published' => 'cf-badge--published',
        'archived' => 'cf-badge--archived',
        default => 'cf-badge--default',
    };
@endphp

<span {{ $attributes->merge(['class' => "cf-badge {$variantClass}"]) }}>
    {{ $slot }}
</span>
