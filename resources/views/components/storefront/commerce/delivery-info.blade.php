@props([
    'summary' => null,
])

@if ($summary)
    <div {{ $attributes->merge(['class' => 'storefront-delivery']) }}>
        <p class="storefront-delivery__label">{{ __('storefront::storefront.delivery') }}</p>
        <p class="storefront-delivery__summary">{{ $summary }}</p>
    </div>
@endif
