@props([
    'categories' => [],
    'search' => null,
    'category' => null,
    'showCategory' => true,
])

<form
    method="GET"
    action="{{ route('storefront.shop.index') }}"
    {{ $attributes->merge(['class' => 'storefront-header-search' . ($showCategory ? '' : ' storefront-header-search--simple')]) }}
>
    @if ($showCategory)
        <label class="sr-only" for="header-search-category">{{ __('storefront::storefront.filter_category') }}</label>
        <select id="header-search-category" name="category" class="storefront-header-search__category">
            <option value="">{{ __('storefront::storefront.filter_all_categories') }}</option>
            @foreach ($categories as $item)
                <option value="{{ $item->slug }}" @selected($category === $item->slug)>{{ $item->name }}</option>
            @endforeach
        </select>
    @endif

    <label class="sr-only" for="header-search-input">{{ __('storefront::storefront.search_products') }}</label>
    <input
        id="header-search-input"
        type="search"
        name="search"
        value="{{ $search }}"
        class="storefront-header-search__input"
        placeholder="{{ __('storefront::storefront.search_products') }}"
        autocomplete="off"
    >

    <button type="submit" class="storefront-header-search__submit" aria-label="{{ __('storefront::storefront.search') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
        </svg>
    </button>
</form>
