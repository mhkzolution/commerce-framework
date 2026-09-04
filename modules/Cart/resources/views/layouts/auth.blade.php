<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('customers::auth.login_title')) — {{ $storeName }}</title>
    <x-app-fonts />
    @vite(['resources/css/app.css', 'resources/css/storefront/auth.css', 'resources/js/app.js'])
    <x-admin.design-tokens />
    @stack('head')
</head>
<body class="storefront storefront-auth min-h-screen bg-background text-text antialiased">
    <main class="storefront-auth-main">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
