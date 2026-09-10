<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="admin-auth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('iam::auth.sign_in')) — {{ $siteBrandName ?? config('admin.name', config('commerce.name')) }}</title>
    <x-brand-favicon :url="$siteBrand?->faviconUrl" />
    <x-app-fonts />
    @vite(['resources/css/app.css', 'resources/css/admin.css'])
    <x-admin.design-tokens />
</head>
<body class="admin-auth-body">
    <main class="admin-auth-page" data-admin-auth>
        <section class="admin-auth-card" aria-labelledby="admin-auth-title">
            @php
                $brandName = $siteBrandName ?? config('admin.name', config('commerce.name'));
                $logoUrl = $siteBrand?->logoUrl;
            @endphp

            <div class="admin-auth-brand">
                @if (! empty($logoUrl))
                    <img
                        src="{{ $logoUrl }}"
                        alt="{{ $brandName }}"
                        class="admin-auth-brand__logo"
                    >
                @endif
                <p class="admin-auth-brand__name">{{ $brandName }}</p>
            </div>

            <h1 id="admin-auth-title" class="admin-auth-title">@yield('heading')</h1>

            @hasSection('description')
                <p class="admin-auth-copy">@yield('description')</p>
            @endif

            @yield('content')
        </section>
    </main>
</body>
</html>
