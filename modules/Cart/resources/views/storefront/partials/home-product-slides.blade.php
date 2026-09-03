@forelse ($arrivalProducts as $index => $product)
    @php
        $variant = $product->defaultVariant();
        $available = $variant ? ($stockLevels[$variant->uuid] ?? null) : null;
    @endphp
    @if ($variant)
        <div class="storefront-slider__slide storefront-home-arrivals__slide">
            <x-storefront.cards.product-card
                :product="$product"
                :variant="$variant"
                :display-currency="$displayCurrency"
                :base-currency="$baseCurrency"
                :currency-converter="$currencyConverter"
                :available="$available"
                :priority="$index < 2"
            />
        </div>
    @endif
@empty
    <div class="storefront-slider__slide storefront-home-arrivals__empty">
        <x-storefront.layout.empty-state :title="__('storefront::storefront.no_products')" />
    </div>
@endforelse
