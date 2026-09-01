@props(['url', 'title' => null])
<x-storefront.buttons.share-button :url="$url" :title="$title" {{ $attributes }} />
