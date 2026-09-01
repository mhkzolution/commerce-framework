@props([
    'filters',
    'categories' => collect(),
    'collections' => collect(),
    'brands' => collect(),
    'filterCatalog' => [],
])

@php
    use Illuminate\Support\Arr;

    $query = $filters->toQueryArray();
    $chips = [];

    if ($filters->search) {
        $chips[] = [
            'label' => $filters->search,
            'url' => route('storefront.shop.index', Arr::except($query, ['search'])),
        ];
    }

    if ($filters->category) {
        $categoryName = $categories->firstWhere('slug', $filters->category)?->name ?? $filters->category;
        $chips[] = [
            'label' => $categoryName,
            'url' => route('storefront.shop.index', Arr::except($query, ['category'])),
        ];
    }

    if ($filters->collection) {
        $collectionName = $collections->firstWhere('slug', $filters->collection)?->name ?? $filters->collection;
        $chips[] = [
            'label' => $collectionName,
            'url' => route('storefront.shop.index', Arr::except($query, ['collection'])),
        ];
    }

    if ($filters->brand) {
        $brandName = $brands->firstWhere('uuid', $filters->brand)?->name ?? $filters->brand;
        $chips[] = [
            'label' => $brandName,
            'url' => route('storefront.shop.index', Arr::except($query, ['brand'])),
        ];
    }

    if ($filters->priceMin !== null || $filters->priceMax !== null) {
        $matchedPreset = collect($filterCatalog['pricePresets'] ?? [])
            ->first(fn (array $preset): bool => $filters->matchesPricePreset($preset['min'], $preset['max']));

        $chips[] = [
            'label' => $matchedPreset['label'] ?? __('storefront::storefront.filter_price'),
            'url' => route('storefront.shop.index', Arr::except($query, ['price_min', 'price_max'])),
        ];
    }

    foreach ([
        'size' => __('storefront::storefront.filter_size'),
        'color' => __('storefront::storefront.filter_color'),
        'age' => __('storefront::storefront.filter_age'),
        'gender' => __('storefront::storefront.filter_gender'),
    ] as $key => $prefix) {
        $value = $filters->{$key};

        if ($value) {
            $chips[] = [
                'label' => $value,
                'url' => route('storefront.shop.index', Arr::except($query, [$key])),
            ];
        }
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
