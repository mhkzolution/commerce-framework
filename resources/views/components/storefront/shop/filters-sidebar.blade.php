@props([
    'filters',
    'filterCatalog',
    'categories' => [],
])

<aside
    {{ $attributes->merge([
        'class' => 'storefront-shop__sidebar storefront-shop-filters-sidebar',
        'aria-label' => __('storefront::storefront.filters'),
    ]) }}
>
    <div class="storefront-shop__sidebar-inner">
        <h2 class="storefront-shop__sidebar-title">{{ __('storefront::storefront.filters') }}</h2>
        <x-storefront.shop.filters-form
            :filters="$filters"
            :filter-catalog="$filterCatalog"
            :categories="$categories"
            form-id="shop-filters-desktop"
            variant="panel"
            data-shop-filters
        >
            <x-slot:actions>
                <a href="{{ route('storefront.shop.index') }}" class="storefront-filters__clear">{{ __('storefront::storefront.clear_filters') }}</a>
                <button type="submit" class="storefront-filters__apply">{{ __('storefront::storefront.apply_filters') }}</button>
            </x-slot:actions>
        </x-storefront.shop.filters-form>
    </div>
</aside>
