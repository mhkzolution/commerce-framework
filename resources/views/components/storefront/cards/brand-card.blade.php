@props([
    'name',
    'url' => null,
    'logoUrl' => null,
])

@php
    $url ??= '#';
@endphp

<a href="{{ $url }}" {{ $attributes->merge(['class' => 'storefront-brand-card']) }}>
    <span class="storefront-brand-card__logo">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $name }}" loading="lazy" decoding="async">
        @else
            <span class="storefront-brand-card__monogram" aria-hidden="true">{{ mb_substr($name, 0, 1) }}</span>
        @endif
    </span>
    <span class="storefront-brand-card__name">{{ $name }}</span>
</a>
