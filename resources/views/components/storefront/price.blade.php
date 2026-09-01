@props(['amount', 'currency', 'minor' => false])
<x-storefront.commerce.price :amount="$amount" :currency="$currency" :minor="$minor" {{ $attributes }} />
