@props(['title', 'description' => null])
<x-storefront.layout.empty-state :title="$title" :description="$description" {{ $attributes }}>{{ $slot }}</x-storefront.layout.empty-state>
