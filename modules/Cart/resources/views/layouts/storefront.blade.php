<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('storefront::storefront.shop')) — {{ app(\Commerce\Contracts\Settings\SiteIdentityServiceInterface::class)->name() }}</title>
    <x-site.favicon />
    <x-site.fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-admin.design-tokens />
    @stack('head')
</head>
<body class="storefront bg-background font-sans text-text antialiased">
    <x-storefront.layout.partials.site-header />

    <main class="@yield('main_class', 'storefront-main')">
        @yield('content')
    </main>

    <x-storefront.layout.partials.site-footer />

    <x-storefront.layout.partials.site-overlays />

    <x-storefront.money-config />

    @stack('scripts')
</body>
</html>
