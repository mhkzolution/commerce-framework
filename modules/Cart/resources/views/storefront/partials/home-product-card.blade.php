<x-storefront.cards.product
    :product="$product"
    :display-currency="$displayCurrency ?? ''"
    :base-currency="$baseCurrency ?? null"
    :currency-converter="$currencyConverter ?? null"
    :priority="$priority ?? false"
/>
