@props([
    'letters' => [],
])

@php
    $letters = array_values(array_filter(is_array($letters) ? $letters : [], static fn ($letter): bool => is_string($letter) && $letter !== ''));
@endphp

@if ($letters !== [])
    <nav
        {{ $attributes->merge(['class' => 'storefront-shop-category-strip storefront-brands-alpha']) }}
        data-brands-alpha
        aria-label="{{ __('storefront::storefront.brands_az_label') }}"
    >
        @foreach ($letters as $letter)
            <a
                class="storefront-shop-category-strip__link storefront-brands-alpha__letter"
                href="#brand-letter-{{ \Commerce\Cart\Services\StorefrontBrandDirectory::letterAnchor($letter) }}"
                data-brands-alpha-letter="{{ $letter }}"
            >
                {{ $letter }}
            </a>
        @endforeach
    </nav>
@endif
