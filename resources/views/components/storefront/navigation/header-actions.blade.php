@props([
    'cartItemCount' => 0,
    'cartUrl' => '/cart',
    'header' => null,
])

<div {{ $attributes->merge(['class' => 'storefront-header-actions']) }}>
    <button
        type="button"
        class="storefront-header-actions__button"
        data-drawer-open="wishlist"
        aria-label="{{ __('storefront::storefront.wishlist') }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path d="M12 20.5 4.5 12.8C2.7 11 2.4 8.2 3.8 6s4.2-2.4 6.4-1.1L12 6.3l2-1.4c2.2-1.3 5-.7 6.4 1.1s1.1 5-1.1 6.8L12 20.5Z" />
        </svg>
        <span class="storefront-header-actions__count" data-wishlist-count hidden>0</span>
    </button>

    <button
        type="button"
        class="storefront-header-actions__button"
        data-drawer-open="cart"
        aria-label="{{ __('storefront::storefront.cart') }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path d="M6 6h15l-1.5 9h-12z" />
            <circle cx="9" cy="20" r="1" />
            <circle cx="18" cy="20" r="1" />
            <path d="M6 6 5 3H2" />
        </svg>
        @if ($cartItemCount > 0)
            <span class="storefront-header-actions__count">{{ $cartItemCount }}</span>
        @endif
    </button>

    <div class="storefront-header-actions__cluster storefront-header-actions__cluster--mobile">
        <x-storefront.navigation.header-account class="storefront-header-account--inline" :header="$header" />

        <button
            type="button"
            class="storefront-header-actions__button storefront-header-actions__button--menu"
            data-drawer-open="mobile-nav"
            aria-label="{{ __('storefront::storefront.nav_menu') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true">
                <path d="M4 7h16" />
                <path d="M4 12h16" />
                <path d="M4 17h16" />
            </svg>
        </button>
    </div>
</div>
