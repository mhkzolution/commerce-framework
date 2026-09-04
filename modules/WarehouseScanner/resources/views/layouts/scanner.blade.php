<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scanner-shell h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('warehouse::scanner.title')) — {{ config('admin.name', config('commerce.name')) }}</title>
    <x-app-fonts />
    @vite(['resources/css/app.css', 'resources/css/scanner.css', 'resources/js/scanner/index.js'])
    <x-admin.design-tokens />
    @stack('head')
</head>
<body class="h-full font-sans antialiased">
    @yield('content')
    @stack('scripts')
</body>
</html>
