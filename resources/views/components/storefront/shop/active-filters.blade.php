@props([
    'filters',
    'categories' => [],
    'filterCatalog' => null,
    'listing' => null,
])

@php
    use Commerce\Cart\DTO\ShopCategoryStripData;
    use Commerce\Cart\DTO\ShopFilterCatalog;
    use Commerce\Cart\DTO\ShopListingContext;
    use Commerce\Cart\DTO\ShopListingFilters;

    $filters = $filters instanceof ShopListingFilters ? $filters : null;
    $filterCatalog = $filterCatalog instanceof ShopFilterCatalog ? $filterCatalog : new ShopFilterCatalog();
    $listing = $listing instanceof ShopListingContext ? $listing : ShopListingContext::shop();
    $chips = [];

    if ($filters?->search) {
        $chips[] = [
            'label' => $filters->search,
            'url' => $listing->urlWith($filters, ['q' => null]),
        ];
    }

    if ($filters?->category) {
        $categoryName = ShopCategoryStripData::findInTree($categories, $filters->category)?->name
            ?? $filters->category;
        $chips[] = [
            'label' => $categoryName,
            'url' => $listing->urlWith($filters, ['category' => null]),
        ];
    }

    if ($filters?->brand && ! $listing->lockBrand) {
        $brandName = $filters->brand;
        foreach ($filterCatalog->brands as $brand) {
            if ($brand['slug'] === $filters->brand) {
                $brandName = $brand['name'];
                break;
            }
        }
        $chips[] = [
            'label' => $brandName,
            'url' => $listing->urlWith($filters, ['brand' => null]),
        ];
    }

    if ($filters !== null && ($filters->priceMin !== null || $filters->priceMax !== null)) {
        $matchedPreset = collect($filterCatalog->pricePresets)
            ->first(fn (array $preset): bool => $filters->matchesPricePreset($preset['min'], $preset['max']));

        $chips[] = [
            'label' => $matchedPreset['label'] ?? __('storefront::storefront.filter_price'),
            'url' => $listing->urlWith($filters, ['price_min' => null, 'price_max' => null]),
        ];
    }

    if ($filters?->size) {
        $chips[] = [
            'label' => $filters->size,
            'url' => $listing->urlWith($filters, ['size' => null]),
        ];
    }

    if ($filters?->color) {
        $chips[] = [
            'label' => $filters->color,
            'url' => $listing->urlWith($filters, ['color' => null]),
        ];
    }

    foreach ($filters?->attributes ?? [] as $code => $selectedValue) {
        if (in_array($code, ['size', 'color'], true)) {
            continue;
        }

        $label = $selectedValue;
        foreach ($filterCatalog->facets as $facet) {
            if ($facet['code'] !== $code) {
                continue;
            }
            foreach ($facet['values'] as $value) {
                if ($value['code'] === $selectedValue) {
                    $label = $facet['name'].': '.$value['label'];
                    break 2;
                }
            }
        }

        $chips[] = [
            'label' => $label,
            'url' => $listing->urlWith($filters, [$code => null]),
        ];
    }

    if ($filters?->availability === 'in_stock') {
        $chips[] = [
            'label' => __('storefront::storefront.availability_in_stock'),
            'url' => $listing->urlWith($filters, ['availability' => null]),
        ];
    }
@endphp

@if ($chips !== [])
    <div class="storefront-active-filters" aria-label="{{ __('storefront::storefront.active_filters') }}">
        <div class="storefront-active-filters__track">
            @foreach ($chips as $chip)
                <a href="{{ $chip['url'] }}" class="storefront-active-filters__chip">
                    <span>{{ $chip['label'] }}</span>
                    <span class="storefront-active-filters__remove" aria-hidden="true">&times;</span>
                </a>
            @endforeach
        </div>
        <a href="{{ $listing->url() }}" class="storefront-active-filters__clear">
            {{ __('storefront::storefront.clear_filters') }}
        </a>
    </div>
@endif
