@props([
    'count' => 0,
    'sort' => 'latest',
    'query' => [],
    'listing' => null,
])

@php
    use Commerce\Cart\DTO\ShopListingContext;

    $listing = $listing instanceof ShopListingContext ? $listing : ShopListingContext::shop();
    $query = is_array($query) ? $query : [];
    $storeAccess = $storeAccess ?? (app()->bound(\Commerce\Contracts\Storefront\StorefrontAccessContext::class)
        ? app(\Commerce\Contracts\Storefront\StorefrontAccessContext::class)
        : null);
    $canViewPrices = $storeAccess?->canViewPrices ?? true;

    $sortOptions = [
        'latest' => __('storefront::storefront.sort_latest'),
    ];
    if ($canViewPrices) {
        $sortOptions['price_asc'] = __('storefront::storefront.sort_price_asc');
        $sortOptions['price_desc'] = __('storefront::storefront.sort_price_desc');
    }
@endphp

<div {{ $attributes->merge(['class' => 'storefront-shop-toolbar']) }}>
    <p class="storefront-shop-toolbar__count" data-shop-count>
        {{ trans_choice('storefront::storefront.shop_results', $count, ['count' => $count]) }}
    </p>

    <div class="storefront-shop-toolbar__actions">
        <button
            type="button"
            class="storefront-shop-toolbar__filters-btn"
            data-filters-sheet-open
        >
            {{ __('storefront::storefront.filters') }}
        </button>

        <x-storefront.forms.sort-dropdown
            :action="$listing->url()"
            name="sort"
            :value="$sort"
            :options="$sortOptions"
            class="storefront-shop-toolbar__sort"
        >
            @foreach (collect($query)->except('sort') as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
        </x-storefront.forms.sort-dropdown>
    </div>

    <div class="storefront-shop-toolbar__view" hidden></div>
</div>
