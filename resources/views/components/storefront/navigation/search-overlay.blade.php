@props([
    'search' => null,
    'brand' => null,
])

@php
    use Commerce\Contracts\Storefront\HeaderBrandData;

    $brand = $brand instanceof HeaderBrandData ? $brand : null;
    $popularTerms = config('cart.storefront.search.popular_terms', []);
    $shopUrl = route('storefront.shop.index');
@endphp

<div
    class="storefront-search-overlay"
    data-search-overlay
    data-shop-url="{{ $shopUrl }}"
    hidden
>
    <div class="storefront-search-overlay__backdrop" data-search-close></div>

    <div class="storefront-search-overlay__sheet" role="dialog" aria-modal="true" aria-label="{{ __('storefront::storefront.search') }}">
        <div class="storefront-search-overlay__toolbar">
            <div class="storefront-search-overlay__logo">
                @if ($brand?->logoUrl)
                    <img src="{{ $brand->logoUrl }}" alt="{{ $brand->name }}" decoding="async">
                @else
                    <span class="storefront-brand__name">{{ $brand?->name }}</span>
                @endif
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
        </div>
    </div>
</div>
