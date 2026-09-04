@props([
    'header' => null,
])

@php
    use Commerce\Contracts\Storefront\HeaderViewData;

    $header = $header instanceof HeaderViewData ? $header : null;
@endphp

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
            href="{{ $header?->actions->loginUrl ?? route('storefront.account.login') }}"
            class="storefront-user-menu__link"
            role="menuitem"
        >
            {{ __('storefront::storefront.sign_in') }}
        </a>

        @if (Route::has('storefront.account.register'))
            <a
                href="{{ route('storefront.account.register') }}"
                class="storefront-user-menu__link"
                role="menuitem"
            >
                {{ __('storefront::storefront.create_account') }}
            </a>
        @endif

        <div class="storefront-user-menu__divider"></div>

        <x-storefront.navigation.user-menu-prefs
            :store-currencies="$header?->actions->currencyCodes ?? []"
            :store-display-currency="$header?->actions->currentCurrency"
            :currency-action-url="$header?->actions->currencyActionUrl"
            id-prefix="guest-menu"
        />
    </div>
</div>
