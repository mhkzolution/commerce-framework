@props([
    'legend',
    'name',
    'categories' => [],
    'categoryImageUrls' => [],
    'selected' => null,
])

@if (filled($categories))
    <fieldset class="storefront-filters__group" data-filter-collapsible data-category-filter>
        <legend class="storefront-filters__legend">{{ $legend }}</legend>

        @if ($categories->count() > 6)
            <div class="storefront-filters__brand-search">
                <input
                    type="search"
                    class="cf-input"
                    placeholder="{{ __('storefront::storefront.filter_category_search') }}"
                    data-category-filter-search
                    autocomplete="off"
                >
            </div>
        @endif

        <div
            class="storefront-filters__options storefront-filters__options--wrap storefront-filters__options--collapsible storefront-filters__brand-list"
            data-filter-options
            data-collapsed="true"
        >
            @foreach ($categories as $category)
                @php
                    $imageUrl = $categoryImageUrls[$category->slug] ?? null;
                @endphp
                <label class="storefront-filters__chip" data-category-filter-item data-category-name="{{ Str::lower($category->name) }}">
                    <input type="radio" name="{{ $name }}" value="{{ $category->slug }}" @checked($selected === $category->slug)>
                    <span class="storefront-filters__chip-label">
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}" alt="" class="storefront-filters__chip-thumb" loading="lazy">
                        @endif
                        <span>{{ $category->name }}</span>
                    </span>
                </label>
            @endforeach
        </div>

        <button type="button" class="storefront-filters__toggle" data-filter-toggle hidden>
            <span data-filter-toggle-more>{{ __('storefront::storefront.filter_show_more') }}</span>
            <span data-filter-toggle-less hidden>{{ __('storefront::storefront.filter_show_less') }}</span>
        </button>
    </fieldset>
@endif
