@props([
    'name',
    'url',
    'productCount' => 0,
    'logoUrl' => null,
    'monogram' => null,
    'letter' => null,
    'slug' => null,
])

@php
    $monogram = is_string($monogram) && $monogram !== ''
        ? $monogram
        : \Commerce\Cart\Services\StorefrontBrandDirectory::monogramFor((string) $name);
@endphp

<a
    href="{{ $url }}"
    {{ $attributes->class('storefront-brand-card') }}
    data-brand-card
    data-brand-name="{{ $name }}"
    data-brand-slug="{{ $slug }}"
    @if ($letter) data-brand-letter="{{ $letter }}" @endif
>
    <span class="storefront-brand-card__logo" aria-hidden="true">
        @if (is_string($logoUrl) && $logoUrl !== '')
            <img
                src="{{ $logoUrl }}"
                alt=""
                width="40"
                height="40"
                loading="lazy"
                decoding="async"
                class="storefront-brand-card__image"
            >
        @else
            <span class="storefront-brand-card__monogram">{{ $monogram }}</span>
        @endif
    </span>
    <span class="storefront-brand-card__copy">
        <span class="storefront-brand-card__name">{{ $name }}</span>
        <span class="storefront-brand-card__count">
            {{ trans_choice('storefront::storefront.brand_product_count', (int) $productCount, ['count' => (int) $productCount]) }}
        </span>
    </span>
</a>
