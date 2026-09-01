@props([
    'filters',
    'filterCatalog' => [],
    'categories' => [],
    'filterCategories' => collect(),
    'categoryImageUrls' => [],
    'brands' => [],
    'brandLogoUrls' => [],
    'formId' => 'shop-filters',
    'variant' => null,
])

@php
    $pricePresets = $filterCatalog['pricePresets'] ?? [];
    $sizeOptions = $filterCatalog['sizeOptions'] ?? [];
    $colorOptions = $filterCatalog['colorOptions'] ?? [];
    $ageOptions = $filterCatalog['ageOptions'] ?? [];
    $genderOptions = $filterCatalog['genderOptions'] ?? [];
    $brandOptions = $brands->mapWithKeys(fn ($brand) => [$brand->slug => $brand->name])->all();
    $selectedBrand = $filters->brand;
    if ($selectedBrand && $brands->isNotEmpty()) {
        $matchedBrand = $brands->first(
            fn ($brand) => $brand->slug === $selectedBrand || $brand->uuid === $selectedBrand,
        );
        $selectedBrand = $matchedBrand?->slug ?? $selectedBrand;
    }
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
    @if ($filters->category)
        <input type="hidden" name="category" value="{{ $filters->category }}">
    @endif
    @if ($filters->collection)
        <input type="hidden" name="collection" value="{{ $filters->collection }}">
    @endif
    @if ($filters->brand)
        <input type="hidden" name="brand" value="{{ $filters->brand }}">
    @endif

    @if (isset($actions))
        <div class="storefront-filters__scroll">
    @endif

    @if ($genderOptions !== [])
        <x-storefront.forms.filter-chip-group
            :legend="__('storefront::storefront.filter_gender')"
            name="gender"
            :options="$genderOptions"
            :selected="$filters->gender"
        />
    @endif

    @if ($brands->isNotEmpty())
        <x-storefront.forms.filter-chip-group
            :legend="__('storefront::storefront.filter_brand')"
            name="brand"
            :options="$brandOptions"
            :selected="$selectedBrand"
        />
    @endif

    <fieldset class="storefront-filters__group" data-price-filter>
        <legend class="storefront-filters__legend">{{ __('storefront::storefront.filter_price') }}</legend>
        <div class="storefront-filters__price-row">
            <input
                type="number"
                name="price_min"
                value="{{ $filters->priceMin ?? '' }}"
                class="cf-input storefront-filters__price-input"
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
                class="cf-input storefront-filters__price-input"
                placeholder="{{ __('storefront::storefront.filter_price_max') }}"
                min="0"
                step="1"
                inputmode="numeric"
                data-price-max-input
            >
        </div>
        <div class="storefront-filters__options storefront-filters__options--wrap storefront-filters__price-presets">
            @foreach ($pricePresets as $preset)
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
    </fieldset>

    @if ($sizeOptions !== [])
        <x-storefront.forms.filter-chip-group
            :legend="__('storefront::storefront.filter_size')"
            name="size"
            :options="$sizeOptions"
            :selected="$filters->size"
        />
    @endif

    @if ($colorOptions !== [])
        <x-storefront.forms.filter-chip-group
            :legend="__('storefront::storefront.filter_color')"
            name="color"
            :options="$colorOptions"
            :selected="$filters->color"
        />
    @endif

    @if ($ageOptions !== [])
        <x-storefront.forms.filter-chip-group
            :legend="__('storefront::storefront.filter_age')"
            name="age"
            :options="$ageOptions"
            :selected="$filters->age"
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
