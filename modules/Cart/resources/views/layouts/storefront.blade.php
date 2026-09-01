<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Shop') — {{ config('commerce.name', 'Commerce Framework') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-admin.design-tokens />
    @stack('head')
</head>
<body class="storefront min-h-screen bg-background text-text antialiased">
    <header class="storefront-header">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <a href="{{ route('storefront.shop.index') }}" class="storefront-brand">{{ config('commerce.name', 'Commerce Framework') }}</a>
            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('storefront.shop.index') }}" class="storefront-nav-link">Shop</a>
                @if (Route::has('storefront.cms.posts.index'))
                    <a href="{{ route('storefront.cms.posts.index') }}" class="storefront-nav-link">Blog</a>
                @endif
                <a href="{{ route('storefront.cart.index') }}" class="storefront-nav-link">Cart</a>
                @if (!empty($storeCurrencies) && Route::has('storefront.cart.currency'))
                    <form method="POST" action="{{ route('storefront.cart.currency') }}" class="inline">
                        @csrf
                        <select name="currency" onchange="this.form.submit()" class="cf-input w-auto py-1">
                            @foreach ($storeCurrencies as $currency)
                                <option value="{{ $currency->code }}" @selected(($storeDisplayCurrency ?? $storeBaseCurrency ?? 'USD') === $currency->code)>
                                    {{ $currency->code }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
                @auth('customer')
                    <a href="{{ route('storefront.account') }}" class="storefront-nav-link">Account</a>
                    <form method="POST" action="{{ route('storefront.account.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="storefront-nav-link">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('storefront.account.login') }}" class="storefront-nav-link">Sign in</a>
                @endauth
            </nav>
        </div>
    </header>
    <main class="@yield('main_class', 'mx-auto w-full max-w-5xl px-6 py-8')">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
