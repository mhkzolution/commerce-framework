@props([
    'filters',
    'filterCatalog' => [],
    'categories' => [],
    'filterCategories' => collect(),
    'brands' => [],
    'categoryImageUrls' => [],
    'brandLogoUrls' => [],
])

<div class="storefront-filters-sheet" data-filters-sheet hidden>
    <div class="storefront-filters-sheet__backdrop" data-filters-sheet-close></div>
    <div class="storefront-filters-sheet__panel" role="dialog" aria-modal="true" aria-label="{{ __('storefront::storefront.filters') }}">
        <div class="storefront-filters-sheet__header">
            <h2 class="storefront-filters-sheet__title">{{ __('storefront::storefront.filters') }}</h2>
            <button type="button" class="storefront-filters-sheet__close" data-filters-sheet-close aria-label="{{ __('storefront::storefront.close') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="storefront-filters-sheet__body">
            <x-storefront.forms.filters-form
                :filters="$filters"
                :filter-catalog="$filterCatalog"
                :categories="$categories"
                :filter-categories="$filterCategories"
                :brands="$brands"
                :category-image-urls="$categoryImageUrls"
                :brand-logo-urls="$brandLogoUrls"
                form-id="shop-filters-mobile"
                data-shop-filters
            >
                <x-slot:actions>
                    <a href="{{ route('storefront.shop.index') }}" class="storefront-filters__clear">{{ __('storefront::storefront.clear_filters') }}</a>
                    <x-admin.button type="submit" variant="primary">{{ __('storefront::storefront.apply_filters') }}</x-admin.button>
                </x-slot:actions>
            </x-storefront.forms.filters-form>
        </div>
    </div>
</div>
