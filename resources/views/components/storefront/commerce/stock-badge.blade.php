@props([
    'available',
])

@if ($available <= 0)
    <span {{ $attributes->merge(['class' => 'storefront-stock storefront-stock--out']) }}>{{ __('storefront::storefront.out_of_stock') }}</span>
@elseif ($available <= 5)
    <span {{ $attributes->merge(['class' => 'storefront-stock storefront-stock--low']) }}>{{ __('storefront::storefront.low_stock', ['count' => $available]) }}</span>
@endif
