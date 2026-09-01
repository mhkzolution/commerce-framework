@props([
    'storeLocales' => [],
    'storeDisplayLocale' => null,
    'storeCurrencies' => [],
    'storeDisplayCurrency' => null,
    'storeBaseCurrency' => null,
])

@php
    /** @var \Commerce\Customers\Models\Customer|null $customer */
    $customer = auth('customer')->user();
    $initials = collect(explode(' ', (string) $customer?->name))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $displayName = collect(explode(' ', trim((string) $customer?->name)))
        ->filter()
        ->first() ?: $customer?->name;
    $currentRoute = request()->route()?->getName();
@endphp

<div class="storefront-user-menu" data-user-menu>
    <button type="button" class="storefront-user-menu__avatar" data-user-menu-toggle aria-label="{{ __('storefront::storefront.account') }}">
        <span class="storefront-user-menu__avatar-mark" aria-hidden="true">{{ $initials !== '' ? $initials : 'U' }}</span>
        <span class="storefront-user-menu__avatar-label">
            {{ __('storefront::storefront.desktop_nav_greeting', ['name' => $displayName]) }}
        </span>
    </button>

    <div class="storefront-user-menu__panel" role="menu">
        <a
            href="{{ route('storefront.account') }}"
            class="storefront-user-menu__link {{ $currentRoute === 'storefront.account' ? 'storefront-user-menu__link--active' : '' }}"
            role="menuitem"
        >
            {{ __('storefront::storefront.account_dashboard') }}
        </a>
        <a
            href="{{ route('storefront.account.orders') }}"
            class="storefront-user-menu__link {{ str_starts_with((string) $currentRoute, 'storefront.account.orders') ? 'storefront-user-menu__link--active' : '' }}"
            role="menuitem"
        >
            {{ __('storefront::storefront.account_orders') }}
        </a>
        <a
            href="{{ route('storefront.account.wishlist') }}"
            class="storefront-user-menu__link {{ $currentRoute === 'storefront.account.wishlist' ? 'storefront-user-menu__link--active' : '' }}"
            role="menuitem"
        >
            {{ __('storefront::storefront.wishlist') }}
        </a>
        <a
            href="{{ route('storefront.account.shipping') }}"
            class="storefront-user-menu__link {{ $currentRoute === 'storefront.account.shipping' ? 'storefront-user-menu__link--active' : '' }}"
            role="menuitem"
        >
            {{ __('storefront::storefront.account_shipping') }}
        </a>
        <a
            href="{{ route('storefront.account.profile') }}"
            class="storefront-user-menu__link {{ $currentRoute === 'storefront.account.profile' ? 'storefront-user-menu__link--active' : '' }}"
            role="menuitem"
        >
            {{ __('storefront::storefront.account_profile') }}
        </a>

        <div class="storefront-user-menu__divider"></div>

        <x-storefront.navigation.user-menu-prefs
            :store-locales="$storeLocales"
            :store-display-locale="$storeDisplayLocale"
            :store-currencies="$storeCurrencies"
            :store-display-currency="$storeDisplayCurrency"
            :store-base-currency="$storeBaseCurrency"
            id-prefix="user-menu"
        />

        <div class="storefront-user-menu__divider"></div>

        <form method="POST" action="{{ route('storefront.account.logout') }}">
            @csrf
            <button type="submit" class="storefront-user-menu__link storefront-user-menu__logout" role="menuitem">
                {{ __('storefront::storefront.sign_out') }}
            </button>
        </form>
    </div>
</div>
