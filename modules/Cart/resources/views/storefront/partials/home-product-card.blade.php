<article class="storefront-home-product-card">
    <a href="{{ $product->url }}" class="storefront-home-product-card__link">
        <span class="storefront-home-product-card__media">
            @if ($product->imageUrl)
                <img
                    src="{{ $product->imageUrl }}"
                    alt="{{ $product->name }}"
                    class="storefront-home-product-card__image"
                    @if ($priority ?? false) fetchpriority="high" @else loading="lazy" @endif
                    decoding="async"
                >
            @endif
        </span>
        <span class="storefront-home-product-card__body">
            <span class="storefront-home-product-card__name">{{ $product->name }}</span>
            <span class="storefront-home-product-card__price">
                {{ number_format($product->price / 100, 2) }} {{ $displayCurrency }}
            </span>
            <span class="storefront-home-product-card__stock">
                {{ $product->inStock ? __('storefront::storefront.in_stock') : __('storefront::storefront.out_of_stock') }}
            </span>
        </span>
    </a>
</article>
