@props([
    'search' => null,
])

@php
    $popularTerms = config('cart.storefront.search.popular_terms', []);
    $shopUrl = route('storefront.shop.index');
@endphp

<div
    class="storefront-search-overlay"
    data-search-overlay
    data-search-url="{{ route('api.v1.storefront.search') }}"
    data-shop-url="{{ $shopUrl }}"
    data-search-label-products="{{ __('storefront::storefront.search_section_products') }}"
    data-search-label-categories="{{ __('storefront::storefront.nav_categories') }}"
    data-search-label-collections="{{ __('storefront::storefront.nav_collections') }}"
    data-search-label-brands="{{ __('storefront::storefront.nav_brands') }}"
    data-search-label-view-all="{{ __('storefront::storefront.search_view_all') }}"
    data-search-label-empty="{{ __('storefront::storefront.search_no_results') }}"
    hidden
>
    <div class="storefront-search-overlay__backdrop" data-search-close></div>

    <div class="storefront-search-overlay__sheet" role="dialog" aria-modal="true" aria-label="{{ __('storefront::storefront.search') }}">
        <div class="storefront-search-overlay__toolbar">
            <div class="storefront-search-overlay__logo">
                <x-site.logo />
            </div>

            <form
                method="GET"
                action="{{ $shopUrl }}"
                class="storefront-search-overlay__field"
                data-search-form
            >
                <svg class="storefront-search-overlay__field-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m20 20-3.5-3.5" />
                </svg>

                <label class="sr-only" for="header-search-input">{{ __('storefront::storefront.search_products') }}</label>
                <input
                    id="header-search-input"
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    class="storefront-search-overlay__input"
                    placeholder="{{ __('storefront::storefront.search') }}"
                    autocomplete="off"
                >
            </form>

            <button type="button" class="storefront-search-overlay__cancel" data-search-close>
                {{ __('storefront::storefront.cancel') }}
            </button>
        </div>

        <div class="storefront-search-overlay__content">
            <div class="storefront-search-overlay__hints" data-search-hints>
                @if (count($popularTerms) > 0)
                    <section class="storefront-search-popular">
                        <h2 class="storefront-search-popular__title">{{ __('storefront::storefront.search_popular') }}</h2>
                        <ul class="storefront-search-popular__list">
                            @foreach ($popularTerms as $term)
                                <li>
                                    <a
                                        href="{{ route('storefront.shop.index', ['search' => $term]) }}"
                                        class="storefront-search-popular__pill"
                                    >{{ $term }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <section class="storefront-search-recent" data-search-recent-section hidden>
                    <h2 class="storefront-search-recent__title">{{ __('storefront::storefront.search_recent') }}</h2>
                    <ul class="storefront-search-recent__list" data-search-recent-list></ul>
                </section>
            </div>

            <div class="storefront-search-overlay__results" data-search-results hidden></div>
        </div>
    </div>
</div>
