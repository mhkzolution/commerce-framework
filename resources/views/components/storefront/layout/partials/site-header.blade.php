@props([
    'header' => null,
])

@php
    use Commerce\Contracts\Storefront\HeaderViewData;

    $header = $header instanceof HeaderViewData ? $header : null;
    $primaryNav = $header?->primaryNav ?? ['promo' => ['enabled' => false], 'items' => []];
@endphp

@if ($header !== null)
    @if ($primaryNav['promo']['enabled'] ?? false)
        <x-storefront.navigation.promo-bar
            :message="$primaryNav['promo']['message']"
            :dismissible="$primaryNav['promo']['dismissible'] ?? true"
        />
    @endif

    <header class="storefront-header storefront-site-header" data-storefront-header>
        <div class="storefront-header__utility">
            <x-storefront.layout.page-container class="storefront-header__utility-inner">
                <x-storefront.navigation.header-account
                    class="storefront-header-account--utility"
                    :header="$header"
                />
            </x-storefront.layout.page-container>
        </div>

        <x-storefront.layout.page-container class="storefront-header__bar">
            <div class="storefront-header__logo">
                <a href="{{ $header->brand->homeUrl }}" class="storefront-brand">
                    @if ($header->brand->logoUrl)
                        <img
                            src="{{ $header->brand->logoUrl }}"
                            alt="{{ $header->brand->name }}"
                            class="storefront-brand__logo"
                            decoding="async"
                        >
                    @else
                        <span class="storefront-brand__name">{{ $header->brand->name }}</span>
                    @endif
                </a>
            </div>

            <div class="storefront-header__desktop-only">
                <x-storefront.navigation.primary-nav
                    class="storefront-header__nav storefront-primary-nav-host"
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

                <noscript>
                    <form method="GET" action="{{ $header->actions->searchUrl }}" class="sr-only" role="search">
                        <input type="search" name="search" value="{{ $header->actions->searchQuery }}">
                    </form>
                </noscript>

                <x-storefront.navigation.header-actions
                    class="storefront-header__actions"
                    :cart-url="$header->actions->cartUrl"
                    :cart-item-count="$header->actions->cartCount"
                    :header="$header"
                />
            </div>
        </x-storefront.layout.page-container>

        <div class="storefront-header__mega-backdrop storefront-header__desktop-only" data-mega-menu-backdrop hidden></div>

        <div class="storefront-header__desktop-only">
            @foreach ($primaryNav['items'] ?? [] as $item)
                @if (($item['type'] ?? '') === 'mega')
                    <x-storefront.navigation.mega-menu :item="$item" />
                @endif
            @endforeach
        </div>
    </header>

    <x-storefront.navigation.search-overlay
        :search="$header->actions->searchQuery"
        :brand="$header->brand"
    />

    <x-storefront.navigation.mobile-nav-drawer
        :items="$primaryNav['items'] ?? []"
        :cart-item-count="$header->actions->cartCount"
        :header="$header"
    />

    <x-storefront.navigation.cart-drawer :cart-url="$header->actions->cartUrl" />
    <x-storefront.navigation.wishlist-drawer :authenticated="$header->actions->authenticated" />
@endif
