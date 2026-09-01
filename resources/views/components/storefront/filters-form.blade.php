@props([
    'filters',
    'filterCatalog' => [],
    'categories' => [],
    'filterCategories' => collect(),
    'brands' => [],
    'categoryImageUrls' => [],
    'brandLogoUrls' => [],
    'formId' => 'shop-filters',
])
<x-storefront.forms.filters-form
    :filters="$filters"
    :filter-catalog="$filterCatalog"
    :categories="$categories"
    :filter-categories="$filterCategories"
    :brands="$brands"
    :category-image-urls="$categoryImageUrls"
    :brand-logo-urls="$brandLogoUrls"
    :form-id="$formId"
    {{ $attributes }}
>
    @if (isset($actions))
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endif
</x-storefront.forms.filters-form>
