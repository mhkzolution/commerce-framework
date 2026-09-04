@props([
    'filters',
    'categories' => [],
    'filterCatalog' => null,
])

@php
    use Commerce\Cart\DTO\ShopFilterCatalog;
    use Commerce\Cart\DTO\ShopListingFilters;
    use Illuminate\Support\Arr;

    $filters = $filters instanceof ShopListingFilters ? $filters : null;
    $filterCatalog = $filterCatalog instanceof ShopFilterCatalog ? $filterCatalog : new ShopFilterCatalog();
    $query = $filters?->toQueryArray() ?? [];
    $chips = [];

    if ($filters?->search) {
        $chips[] = [
            'label' => $filters->search,
            'url' => route('storefront.shop.index', Arr::except($query, ['search'])),
        ];
    }

    if ($filters?->category) {
        $categoryName = $filters->category;
        foreach ($categories as $category) {
            if ($category->slug === $filters->category) {
                $categoryName = $category->name;
                break;
            }
        }
        $chips[] = [
            'label' => $categoryName,
            'url' => route('storefront.shop.index', Arr::except($query, ['category'])),
        ];
    }

    if ($filters?->brand) {
        $brandName = $filters->brand;
        foreach ($filterCatalog->brands as $brand) {
            if ($brand['slug'] === $filters->brand) {
                $brandName = $brand['name'];
                break;
            }
        }
        $chips[] = [
            'label' => $brandName,
            'url' => route('storefront.shop.index', Arr::except($query, ['brand'])),
        ];
    }

    if ($filters !== null && ($filters->priceMin !== null || $filters->priceMax !== null)) {
        $matchedPreset = collect($filterCatalog->pricePresets)
            ->first(fn (array $preset): bool => $filters->matchesPricePreset($preset['min'], $preset['max']));

        $chips[] = [
            'label' => $matchedPreset['label'] ?? __('storefront::storefront.filter_price'),
            'url' => route('storefront.shop.index', Arr::except($query, ['price_min', 'price_max'])),
        ];
    }

    if ($filters?->size) {
        $chips[] = [
            'label' => $filters->size,
            'url' => route('storefront.shop.index', Arr::except($query, ['size'])),
        ];
    }

    if ($filters?->color) {
        $chips[] = [
            'label' => $filters->color,
            'url' => route('storefront.shop.index', Arr::except($query, ['color'])),
        ];
    }

    if ($filters?->availability === 'in_stock') {
        $chips[] = [
            'label' => __('storefront::storefront.availability_in_stock'),
            'url' => route('storefront.shop.index', Arr::except($query, ['availability'])),
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
        <a href="{{ route('storefront.shop.index') }}" class="storefront-active-filters__clear">
            {{ __('storefront::storefront.clear_filters') }}
        </a>
    </div>
@endif
