@props([
    'href' => null,
])

@php
    $href ??= route('storefront.shop.index');
@endphp

<x-site.logo :href="$href" variant="auth" {{ $attributes }} />
