@props([
    'name',
    'url' => null,
    'count' => null,
    'imageUrl' => null,
])

@php
    $url ??= '#';
@endphp

<a href="{{ $url }}" {{ $attributes->merge(['class' => 'storefront-category-card']) }}>
    <span class="storefront-category-card__media">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="" class="storefront-category-card__image" loading="lazy" decoding="async">
        @else
            <span class="storefront-category-card__placeholder" aria-hidden="true"></span>
        @endif
    </span>
    <span class="storefront-category-card__name">{{ $name }}</span>
    @if ($count !== null)
        <span class="storefront-category-card__count">{{ $count }}</span>
    @endif
</a>
