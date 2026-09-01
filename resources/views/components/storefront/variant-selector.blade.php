@props(['variants' => [], 'selectedUuid' => null])
<x-storefront.forms.variant-selector :variants="$variants" :selected-uuid="$selectedUuid" {{ $attributes }} />
