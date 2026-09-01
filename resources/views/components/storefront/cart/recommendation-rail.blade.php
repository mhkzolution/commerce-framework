@props([
    'title',
    'products',
    'stockLevels',
    'currency',
])

@php
    $converter = app()->bound(\Commerce\Contracts\Currency\CurrencyConverterInterface::class)
        ? app(\Commerce\Contracts\Currency\CurrencyConverterInterface::class)
        : null;
    $baseCurrency = config('cart.default_currency');
@endphp

@if ($products->isNotEmpty())
    <x-storefront.product-section :title="$title" {{ $attributes }}>
        <x-storefront.product-grid>
            @foreach ($products as $product)
                @php $variant = $product->defaultVariant(); @endphp
                @if ($variant)
                    <x-storefront.product-card
                        :product="$product"
                        :variant="$variant"
                        :display-currency="$currency"
                        :base-currency="$baseCurrency"
                        :currency-converter="$converter"
                        :available="$stockLevels[$variant->uuid] ?? null"
                    />
                @endif
            @endforeach
        </x-storefront.product-grid>
    </x-storefront.product-section>
@endif
