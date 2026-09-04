<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Shop') — {{ config('commerce.name', 'Commerce Framework') }}</title>
    <x-app-fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/storefront/footer.css'])
    <x-admin.design-tokens />
    @stack('head')
</head>
<body class="storefront min-h-screen bg-background text-text antialiased">
    <x-storefront.layout.partials.site-header />
    <main class="@yield('main_class', 'mx-auto w-full max-w-5xl px-6 py-8')">
        @yield('content')
    </main>
    <x-storefront.layout.partials.site-footer />
    <x-storefront.layout.partials.site-overlays />
    @stack('scripts')
</body>
</html>
