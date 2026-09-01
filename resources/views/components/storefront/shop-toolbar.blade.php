@props([
    'filters',
    'total' => 0,
])

<div {{ $attributes->merge(['class' => 'storefront-shop-toolbar']) }}>
    <p class="storefront-shop-toolbar__count" data-shop-count>
        {{ trans_choice('storefront::storefront.products_count', $total, ['count' => $total]) }}
    </p>

    <div class="storefront-shop-toolbar__actions">
        <button type="button" class="cf-btn cf-btn--secondary storefront-shop-toolbar__filters-btn" data-filters-sheet-open>
            {{ __('storefront::storefront.filters') }}
        </button>

        <form method="GET" action="{{ route('storefront.shop.index') }}" class="storefront-shop-toolbar__sort">
            <x-storefront.hidden-filters :filters="$filters" :except="['sort']" />
            <label class="sr-only" for="shop-sort">{{ __('storefront::storefront.sort') }}</label>
            <select id="shop-sort" name="sort" class="cf-input storefront-shop-toolbar__sort-select" onchange="this.form.submit()">
                <option value="newest" @selected($filters->sort === 'newest')>{{ __('storefront::storefront.sort_newest') }}</option>
                <option value="price_asc" @selected($filters->sort === 'price_asc')>{{ __('storefront::storefront.sort_price_asc') }}</option>
                <option value="price_desc" @selected($filters->sort === 'price_desc')>{{ __('storefront::storefront.sort_price_desc') }}</option>
                <option value="name_asc" @selected($filters->sort === 'name_asc')>{{ __('storefront::storefront.sort_name') }}</option>
                <option value="rating" @selected($filters->sort === 'rating')>{{ __('storefront::storefront.sort_rating') }}</option>
            </select>
        </form>
    </div>
</div>
