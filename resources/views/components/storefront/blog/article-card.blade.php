@props([
    'post',
    'blogService',
    'featured' => false,
])
<x-storefront.cards.blog-card :post="$post" :blog-service="$blogService" :featured="$featured" {{ $attributes }} />
