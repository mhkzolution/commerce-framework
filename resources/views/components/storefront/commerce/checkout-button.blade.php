@props([
    'href' => null,
    'label' => null,
])

@php
    $href ??= route('storefront.checkout');
    $label ??= __('storefront::storefront.checkout');
@endphp

<x-storefront.buttons.primary-button :href="$href" {{ $attributes->merge(['class' => 'storefront-checkout-button']) }}>
    {{ $label }}
</x-storefront.buttons.primary-button>
