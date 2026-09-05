@props([
    'header' => null,
])

@php
    use Commerce\Contracts\Storefront\HeaderViewData;

    $header = $header instanceof HeaderViewData ? $header : null;
    $name = $header?->actions->customerName ?? '';
    $initials = $header?->actions->customerInitials ?: 'U';
    $displayName = collect(explode(' ', trim($name)))->filter()->first() ?: $name;
@endphp

<div class="storefront-user-menu" data-user-menu>
    <button type="button" class="storefront-user-menu__avatar" data-user-menu-toggle aria-label="{{ __('storefront::storefront.account') }}">
        <span class="storefront-user-menu__avatar-mark" aria-hidden="true">{{ $initials }}</span>
        <span class="storefront-user-menu__avatar-label">
            {{ __('storefront::storefront.desktop_nav_greeting', ['name' => $displayName !== '' ? $displayName : __('storefront::storefront.account')]) }}
        </span>
    </button>

    <div class="storefront-user-menu__panel" role="menu">
        <a href="{{ $header?->actions->accountUrl ?? route('storefront.account') }}" class="storefront-user-menu__link" role="menuitem">
            {{ __('storefront::storefront.account_dashboard') }}
        </a>
        @if (Route::has('storefront.account.orders'))
            <a href="{{ route('storefront.account.orders') }}" class="storefront-user-menu__link" role="menuitem">
                {{ __('storefront::storefront.orders') }}
            </a>
        @endif
        @if (Route::has('storefront.account.wishlist'))
            <a href="{{ route('storefront.account.wishlist') }}" class="storefront-user-menu__link" role="menuitem">
                {{ __('storefront::storefront.wishlist') }}
            </a>
        @endif

        <div class="storefront-user-menu__divider"></div>

        <x-storefront.navigation.user-menu-prefs
            :store-currencies="$header?->actions->currencyCodes ?? []"
            :store-display-currency="$header?->actions->currentCurrency"
            :currency-action-url="$header?->actions->currencyActionUrl"
            id-prefix="user-menu"
        />

        <div class="storefront-user-menu__divider"></div>

        <form method="POST" action="{{ $header?->actions->logoutUrl ?? route('storefront.account.logout') }}">
            @csrf
            <button type="submit" class="storefront-user-menu__link storefront-user-menu__logout" role="menuitem">
                {{ __('storefront::storefront.sign_out') }}
            </button>
        </form>
    </div>
</div>
