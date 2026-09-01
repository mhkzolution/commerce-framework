@props([
    'filters',
    'filterCatalog' => [],
    'categories' => [],
    'filterCategories' => collect(),
    'brands' => [],
    'categoryImageUrls' => [],
    'brandLogoUrls' => [],
])

<aside class="storefront-shop__sidebar" aria-label="{{ __('storefront::storefront.filters') }}">
    <div class="storefront-shop__sidebar-inner">
        <h2 class="storefront-shop__sidebar-title">{{ __('storefront::storefront.filters') }}</h2>
        <x-storefront.forms.filters-form
            :filters="$filters"
            :filter-catalog="$filterCatalog"
            :categories="$categories"
            :filter-categories="$filterCategories"
            :brands="$brands"
            :category-image-urls="$categoryImageUrls"
            :brand-logo-urls="$brandLogoUrls"
            form-id="shop-filters-desktop"
            variant="panel"
            data-shop-filters
        >
            <x-slot:actions>
                <a href="{{ route('storefront.shop.index') }}" class="storefront-filters__clear">{{ __('storefront::storefront.clear_filters') }}</a>
                <x-admin.button type="submit" variant="primary">{{ __('storefront::storefront.apply_filters') }}</x-admin.button>
            </x-slot:actions>
        </x-storefront.forms.filters-form>
    </div>
</aside>
