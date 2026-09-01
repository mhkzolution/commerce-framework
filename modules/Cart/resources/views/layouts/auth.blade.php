<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('customers::auth.login_title')) — {{ app(\Commerce\Contracts\Settings\SiteIdentityServiceInterface::class)->name() }}</title>
    <x-site.favicon />
    <x-site.fonts />
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-admin.design-tokens />
</head>
<body class="storefront storefront-auth min-h-screen bg-background font-sans text-text antialiased">
    <main class="storefront-auth-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
