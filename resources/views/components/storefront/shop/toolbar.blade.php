@props([
    'count' => 0,
    'sort' => 'latest',
    'query' => [],
])

@php
    $query = is_array($query) ? $query : [];
@endphp

<div {{ $attributes->merge(['class' => 'storefront-shop-toolbar']) }}>
    <p class="storefront-shop-toolbar__count">
        {{ trans_choice('storefront::storefront.shop_results', $count, ['count' => $count]) }}
    </p>

    <x-storefront.forms.sort-dropdown
        :action="route('storefront.shop.index')"
        name="sort"
        :value="$sort"
        :options="[
            'latest' => __('storefront::storefront.sort_latest'),
            'price_asc' => __('storefront::storefront.sort_price_asc'),
            'price_desc' => __('storefront::storefront.sort_price_desc'),
        ]"
        class="storefront-shop-toolbar__sort"
    >
        @foreach (collect($query)->except('sort') as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
    </x-storefront.forms.sort-dropdown>

    <div class="storefront-shop-toolbar__view" hidden></div>
</div>
