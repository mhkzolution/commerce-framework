@props([
    'storeLocales' => [],
    'storeDisplayLocale' => null,
    'storeCurrencies' => [],
    'storeDisplayCurrency' => null,
    'storeBaseCurrency' => null,
])

<div class="storefront-user-menu" data-user-menu>
    <button type="button" class="storefront-user-menu__avatar" data-user-menu-toggle aria-label="{{ __('storefront::storefront.account') }}">
        <span class="storefront-user-menu__avatar-mark" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                <circle cx="12" cy="8" r="4" />
                <path d="M4 20c1.5-4 6.5-4 8-4s6.5 0 8 4" />
            </svg>
        </span>
        <span class="storefront-user-menu__avatar-label">{{ __('storefront::storefront.sign_in') }}</span>
    </button>

    <div class="storefront-user-menu__panel" role="menu">
        <a
            href="{{ route('storefront.account.login') }}"
            class="storefront-user-menu__link"
            role="menuitem"
        >
            {{ __('storefront::storefront.sign_in') }}
        </a>

        <div class="storefront-user-menu__divider"></div>

        <x-storefront.navigation.user-menu-prefs
            :store-locales="$storeLocales"
            :store-display-locale="$storeDisplayLocale"
            :store-currencies="$storeCurrencies"
            :store-display-currency="$storeDisplayCurrency"
            :store-base-currency="$storeBaseCurrency"
            id-prefix="guest-menu"
        />
    </div>
</div>
