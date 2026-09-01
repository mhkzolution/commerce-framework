@php
    $searchQuery = request()->query('search');
    $primaryNav = $headerPrimaryNavigation ?? ['promo' => ['enabled' => false], 'items' => []];
@endphp

@if ($primaryNav['promo']['enabled'] ?? false)
    <x-storefront.navigation.promo-bar
        :message="$primaryNav['promo']['message']"
        :dismissible="$primaryNav['promo']['dismissible'] ?? true"
    />
@endif

<header class="storefront-header" data-storefront-header>
    <div class="storefront-header__utility">
        <div class="storefront-header__utility-inner">
            <x-storefront.navigation.header-account class="storefront-header-account--utility" />
        </div>
    </div>

    <div class="storefront-header__bar">
        <div class="storefront-header__logo">
            <x-site.logo />
        </div>

        <div class="storefront-header__desktop-only">
            <x-storefront.navigation.primary-nav
                class="storefront-header__nav"
                :items="$primaryNav['items'] ?? []"
            />
        </div>

        <div class="storefront-header__end">
            <button
                type="button"
                class="storefront-header-actions__button storefront-header-actions__button--search"
                data-search-open
                aria-label="{{ __('storefront::storefront.search') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m20 20-3.5-3.5" />
                </svg>
            </button>

            <x-storefront.navigation.header-actions
                class="storefront-header__actions"
                :cart-item-count="$headerCart->itemCount ?? 0"
            />
        </div>
    </div>

    <div class="storefront-header__mega-backdrop storefront-header__desktop-only" data-mega-menu-backdrop hidden></div>

    <div class="storefront-header__desktop-only">
        @foreach ($primaryNav['items'] ?? [] as $item)
            @if (($item['type'] ?? '') === 'mega')
                <x-storefront.navigation.mega-menu :item="$item" />
            @endif
        @endforeach
    </div>
</header>
