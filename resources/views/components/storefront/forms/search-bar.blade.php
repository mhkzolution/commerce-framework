@props([
    'action',
    'method' => 'GET',
    'name' => 'search',
    'placeholder' => null,
    'value' => null,
])

@php
    $placeholder ??= __('storefront::storefront.search_products');
@endphp

<form method="{{ $method }}" action="{{ $action }}" {{ $attributes->merge(['class' => 'storefront-search-bar']) }}>
    {{ $slot }}
    <x-admin.search-input
        :name="$name"
        :placeholder="$placeholder"
        :value="$value"
        autocomplete="off"
    />
</form>
