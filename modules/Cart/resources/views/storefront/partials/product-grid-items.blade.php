@forelse ($products as $index => $product)
    @php
        $variant = $product->defaultVariant();
        $available = $variant ? ($stockLevels[$variant->uuid] ?? null) : null;
    @endphp
    @if ($variant)
        <x-storefront.product-card
            :product="$product"
            :variant="$variant"
            :display-currency="$displayCurrency"
            :base-currency="$baseCurrency"
            :currency-converter="$currencyConverter"
            :available="$available"
            :priority="$index < 4"
        />
    @endif
@empty
    <x-storefront.empty-state :title="__('storefront::storefront.no_products')" />
@endforelse
