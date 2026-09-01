@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('storefront::storefront.shop') }} — {{ app(\Commerce\Contracts\Settings\SiteIdentityServiceInterface::class)->name() }}</title>
    <x-site.favicon />
    <x-site.fonts />
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-admin.design-tokens />
</head>
<body class="storefront bg-background font-sans text-text antialiased">
    <x-storefront.layout.partials.site-header />

    <main class="storefront-main">
        {{ $slot }}
    </main>

    <x-storefront.layout.partials.site-footer />

    <x-storefront.layout.partials.site-overlays />

    @stack('scripts')
</body>
</html>
