@props(['title', 'description' => null])
<x-storefront.layout.section-header :title="$title" :description="$description" {{ $attributes }}>
    @if (isset($actions))
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endif
</x-storefront.layout.section-header>
