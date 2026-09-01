@props([
    'legend',
    'name',
    'brands' => [],
    'brandLogoUrls' => [],
    'selected' => null,
])

@if (filled($brands))
    <fieldset class="storefront-filters__group" data-filter-collapsible data-brand-filter>
        <legend class="storefront-filters__legend">{{ $legend }}</legend>

        @if ($brands->count() > 6)
            <div class="storefront-filters__brand-search">
                <input
                    type="search"
                    class="cf-input"
                    placeholder="{{ __('storefront::storefront.filter_brand_search') }}"
                    data-brand-filter-search
                    autocomplete="off"
                >
            </div>
        @endif

        <div
            class="storefront-filters__options storefront-filters__options--wrap storefront-filters__options--collapsible storefront-filters__brand-list"
            data-filter-options
            data-collapsed="true"
        >
            @foreach ($brands as $brand)
                @php
                    $logoUrl = $brandLogoUrls[$brand->slug] ?? null;
                @endphp
                <label class="storefront-filters__chip" data-brand-filter-item data-brand-name="{{ Str::lower($brand->name) }}">
                    <input type="radio" name="{{ $name }}" value="{{ $brand->slug }}" @checked($selected === $brand->slug || $selected === $brand->uuid)>
                    <span class="storefront-filters__chip-label">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="" class="storefront-filters__chip-thumb" loading="lazy">
                        @endif
                        <span>{{ $brand->name }}</span>
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
