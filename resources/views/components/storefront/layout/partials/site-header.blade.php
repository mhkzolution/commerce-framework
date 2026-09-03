@props([
    'header' => null,
])

@php
    use Commerce\Contracts\Navigation\NavigationLinkData;
    use Commerce\Contracts\Storefront\HeaderViewData;

    $header = $header instanceof HeaderViewData ? $header : null;
@endphp

@if ($header !== null)
    <header class="storefront-site-header">
        <x-storefront.layout.page-container class="storefront-site-header__inner">
            <a href="{{ $header->brand->homeUrl }}" class="storefront-brand storefront-site-header__brand">
                @if ($header->brand->logoUrl)
                    <img
                        src="{{ $header->brand->logoUrl }}"
                        alt="{{ $header->brand->name }}"
                        class="storefront-site-header__logo"
                    >
                @endif
                <span class="storefront-site-header__brand-name">{{ $header->brand->name }}</span>
            </a>

            @if ($header->navigation->links !== [])
                <details class="storefront-site-header__menu">
                    <summary class="storefront-site-header__menu-toggle">Menu</summary>
                    <nav class="storefront-site-header__nav" aria-label="Primary">
                        @foreach ($header->navigation->links as $link)
                            @continue(! $link instanceof NavigationLinkData)
                            <a href="{{ $link->url }}" class="storefront-nav-link">{{ $link->label }}</a>
                        @endforeach
                    </nav>
                </details>
            @endif

            <div class="storefront-site-header__actions">
                <form method="GET" action="{{ $header->actions->searchUrl }}" class="storefront-site-header__search" role="search">
                    <label class="storefront-site-header__search-label">
                        <span class="storefront-site-header__search-caption">Search</span>
                        <input
                            type="search"
                            name="search"
                            placeholder="Search products..."
                            class="storefront-site-header__search-input"
                        >
                    </label>
                </form>

                <a href="{{ $header->actions->cartUrl }}" class="storefront-nav-link storefront-site-header__cart">
                    Cart
                    @if ($header->actions->cartCount > 0)
                        <span class="storefront-site-header__cart-count">{{ $header->actions->cartCount }}</span>
                    @endif
                </a>

                @if ($header->actions->authenticated)
                    <a href="{{ $header->actions->accountUrl }}" class="storefront-nav-link">Account</a>
                    <form method="POST" action="{{ $header->actions->logoutUrl }}" class="storefront-site-header__logout">
                        @csrf
                        <button type="submit" class="storefront-nav-link">Sign out</button>
                    </form>
                @else
                    <a href="{{ $header->actions->loginUrl }}" class="storefront-nav-link">Sign in</a>
                @endif

                @if ($header->actions->currencyCodes !== [] && $header->actions->currencyActionUrl)
                    <form method="POST" action="{{ $header->actions->currencyActionUrl }}" class="storefront-site-header__currency">
                        @csrf
                        <select name="currency" onchange="this.form.submit()" class="storefront-site-header__currency-select" aria-label="Currency">
                            @foreach ($header->actions->currencyCodes as $code)
                                <option value="{{ $code }}" @selected($header->actions->currentCurrency === $code)>
                                    {{ $code }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>
        </x-storefront.layout.page-container>
    </header>
@endif
