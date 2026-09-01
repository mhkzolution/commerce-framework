@php
    $landing = $landing ?? [];
    $heroClass = 'storefront-catalog-landing__hero';
    if (! empty($landing['coverUrl'])) {
        $heroClass .= ' storefront-catalog-landing__hero--has-cover';
    }
    if (($landing['coverVariant'] ?? 'cover') === 'logo') {
        $heroClass .= ' storefront-catalog-landing__hero--logo';
    }
@endphp

<header class="{{ $heroClass }}">
    @if (! empty($landing['coverUrl']))
        <img
            src="{{ $landing['coverUrl'] }}"
            alt=""
            class="storefront-catalog-landing__hero-image"
            loading="eager"
        >
        <div class="storefront-catalog-landing__hero-overlay" aria-hidden="true"></div>
    @endif

    <div class="storefront-catalog-landing__hero-content">
        @if (! empty($landing['eyebrow']))
            <p class="storefront-catalog-landing__eyebrow">{{ $landing['eyebrow'] }}</p>
        @endif
        <h1 class="storefront-catalog-landing__title">{{ $landing['title'] ?? '' }}</h1>
        @if (! empty($landing['description']))
            <p class="storefront-catalog-landing__description">{{ $landing['description'] }}</p>
        @endif
        @if (isset($products))
            <p class="storefront-catalog-landing__meta">
                {{ trans_choice('storefront::storefront.collection_product_count', $products->total(), ['count' => $products->total()]) }}
            </p>
        @endif
    </div>
</header>
