@forelse ($arrivalProducts as $index => $product)
    <div class="storefront-slider__slide storefront-home-arrivals__slide">
        @include('cart::storefront.partials.home-product-card', [
            'product' => $product,
            'displayCurrency' => $displayCurrency ?? '',
            'priority' => $index < 2,
        ])
    </div>
@empty
    <div class="storefront-slider__slide storefront-home-arrivals__empty">
        <x-storefront.empty-state :title="__('storefront::storefront.no_products')" />
    </div>
@endforelse
