@props([
    'filters',
    'filterCatalog',
    'categories' => [],
    'formId' => 'shop-filters',
    'variant' => null,
])

@php
    use Commerce\Cart\DTO\ShopFilterCatalog;
    use Commerce\Cart\DTO\ShopListingFilters;

    $filters = $filters instanceof ShopListingFilters ? $filters : new ShopListingFilters();
    $filterCatalog = $filterCatalog instanceof ShopFilterCatalog ? $filterCatalog : new ShopFilterCatalog();

    $brandOptions = [];
    foreach ($filterCatalog->brands as $brand) {
        $brandOptions[$brand['slug']] = $brand['name'];
    }

    $categoryOptions = [];
    foreach ($categories as $category) {
        $categoryOptions[$category->slug] = $category->name;
    }

    $availabilityOptions = [
        'all' => __('storefront::storefront.availability_all'),
        'in_stock' => __('storefront::storefront.availability_in_stock'),
    ];
@endphp

<form
    id="{{ $formId }}"
    method="GET"
    action="{{ route('storefront.shop.index') }}"
    class="storefront-filters {{ $variant === 'panel' ? 'storefront-filters--panel' : '' }} {{ isset($actions) ? 'storefront-filters--sticky-actions' : '' }}"
    {{ $attributes }}
>
    @if ($filters->search)
        <input type="hidden" name="search" value="{{ $filters->search }}">
    @endif
    @if ($filters->sort !== 'latest')
        <input type="hidden" name="sort" value="{{ $filters->sort }}">
    @endif

    @if (isset($actions))
        <div class="storefront-filters__scroll">
    @endif

    @if ($categoryOptions !== [])
        <x-storefront.shop.filter-chip-group
            :legend="__('storefront::storefront.filter_category')"
            name="category"
            :options="$categoryOptions"
            :selected="$filters->category"
        />
    @endif

    <x-storefront.shop.filter-chip-group
        :legend="__('storefront::storefront.filter_availability')"
        name="availability"
        :options="$availabilityOptions"
        :selected="$filters->availability"
    />

    @if ($brandOptions !== [])
        <x-storefront.shop.filter-chip-group
            :legend="__('storefront::storefront.filter_brand')"
            name="brand"
            :options="$brandOptions"
            :selected="$filters->brand"
        />
    @endif

    <fieldset class="storefront-filters__group" data-price-filter>
        <legend class="storefront-filters__legend">{{ __('storefront::storefront.filter_price') }}</legend>
        <div class="storefront-filters__price-row">
            <input
                type="number"
                name="price_min"
                value="{{ $filters->priceMin ?? '' }}"
                class="storefront-filters__price-input"
                placeholder="{{ __('storefront::storefront.filter_price_min') }}"
                min="0"
                step="1"
                inputmode="numeric"
                data-price-min-input
            >
            <span class="storefront-filters__price-sep" aria-hidden="true">–</span>
            <input
                type="number"
                name="price_max"
                value="{{ $filters->priceMax ?? '' }}"
                class="storefront-filters__price-input"
                placeholder="{{ __('storefront::storefront.filter_price_max') }}"
                min="0"
                step="1"
                inputmode="numeric"
                data-price-max-input
            >
        </div>
        @if ($filterCatalog->pricePresets !== [])
            <div class="storefront-filters__options storefront-filters__options--wrap storefront-filters__price-presets">
                @foreach ($filterCatalog->pricePresets as $preset)
                    @php
                        $isActive = $filters->matchesPricePreset($preset['min'], $preset['max']);
                    @endphp
                    <button
                        type="button"
                        class="storefront-filters__badge {{ $isActive ? 'storefront-filters__badge--active' : '' }}"
                        data-price-preset
                        data-price-min="{{ $preset['min'] ?? '' }}"
                        data-price-max="{{ $preset['max'] ?? '' }}"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                    >
                        {{ $preset['label'] }}
                    </button>
                @endforeach
            </div>
        @endif
    </fieldset>

    @if ($filterCatalog->sizes !== [])
        <x-storefront.shop.filter-chip-group
            :legend="__('storefront::storefront.filter_size')"
            name="size"
            :options="$filterCatalog->sizes"
            :selected="$filters->size"
        />
    @endif

    @if ($filterCatalog->colors !== [])
        <x-storefront.shop.filter-chip-group
            :legend="__('storefront::storefront.filter_color')"
            name="color"
            :options="$filterCatalog->colors"
            :selected="$filters->color"
        />
    @endif

    @if (isset($actions))
        </div>
    @endif

    @if (isset($actions))
        <div class="storefront-filters__actions storefront-filters__actions--row">
            {{ $actions }}
        </div>
    @endif
</form>
