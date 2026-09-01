@props([
    'filters',
    'filterCatalog' => [],
    'categories' => [],
    'filterCategories' => collect(),
    'brands' => [],
    'categoryImageUrls' => [],
    'brandLogoUrls' => [],
])
<x-storefront.forms.filter-panel
    :filters="$filters"
    :filter-catalog="$filterCatalog"
    :categories="$categories"
    :filter-categories="$filterCategories"
    :brands="$brands"
    :category-image-urls="$categoryImageUrls"
    :brand-logo-urls="$brandLogoUrls"
    {{ $attributes }}
/>
