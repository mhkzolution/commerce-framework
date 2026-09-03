@forelse ($arrivalProducts as $index => $product)
    <div class="storefront-slider__slide storefront-home-arrivals__slide">
        <x-storefront.cards.product
            :product="$product"
            :display-currency="$displayCurrency ?? ''"
            :base-currency="$baseCurrency ?? null"
            :currency-converter="$currencyConverter ?? null"
            :priority="$index < 2"
        />
    </div>
@empty
    <div class="storefront-slider__slide storefront-home-arrivals__empty">
        <x-storefront.empty-state :title="__('storefront::storefront.no_products')" />
    </div>
@endforelse
