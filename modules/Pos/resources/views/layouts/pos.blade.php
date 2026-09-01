<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="pos-shell h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS') — {{ app(\Commerce\Contracts\Settings\SiteIdentityServiceInterface::class)->name() }}</title>
    <x-site.favicon />
    <x-site.fonts />
    @vite(['resources/css/app.css', 'resources/css/pos.css', 'resources/js/pos/index.js'])
    <x-admin.design-tokens />
    @stack('head')
</head>
<body class="h-full font-sans antialiased">
    @yield('content')
    @stack('scripts')
</body>
</html>
