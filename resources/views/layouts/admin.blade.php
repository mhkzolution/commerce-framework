<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="admin-shell h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ config('admin.name', config('commerce.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/admin.js'])
    <x-admin.design-tokens />
    @stack('head')
</head>
<body class="h-full antialiased">
    <div class="admin-shell-layout">
        <div id="admin-sidebar-backdrop" class="admin-sidebar-backdrop lg:hidden" aria-hidden="true"></div>

        <aside id="admin-sidebar" class="admin-sidebar" aria-label="Sidebar navigation">
            <div class="admin-sidebar-inner">
                <div class="flex h-[var(--topbar-height)] items-center gap-3 border-b border-border px-4">
                    <div class="admin-brand-mark" aria-hidden="true">C</div>
                    <div class="admin-brand-text min-w-0">
                        <div class="truncate text-sm font-semibold text-text">{{ config('admin.name', config('commerce.name')) }}</div>
                        <div class="truncate text-xs text-muted">Admin</div>
                    </div>
                </div>

                <div class="admin-sidebar-search border-b border-border p-3">
                    <x-admin.search-input id="admin-menu-search" placeholder="Search menu..." name="menu_search" :value="null" />
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto p-3" aria-label="Main">
                    @foreach ($adminNavigation ?? [] as $item)
                        <x-admin.nav-item :item="$item" />
                    @endforeach
                </nav>

                <div class="border-t border-border p-3 text-xs text-muted">
                    <span class="admin-brand-text">v{{ config('commerce.version', '1.0.0-alpha') }}</span>
                </div>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="flex h-full items-center justify-between gap-4 px-4 lg:px-6">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <button id="admin-sidebar-toggle" type="button" class="cf-surface-interactive p-2" aria-label="Toggle sidebar">
                            <x-admin.icon name="bars-3" class="h-5 w-5" />
                        </button>

                        <div class="hidden min-w-0 flex-1 md:block">
                            <x-admin.search-input
                                id="admin-global-search"
                                placeholder="Search products, orders, customers..."
                                name="global_search"
                                class="max-w-md"
                                data-search-url="{{ route('admin.search') }}"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button id="admin-command-open" type="button" class="cf-surface-interactive hidden items-center gap-2 border border-border px-3 py-1.5 text-sm text-muted sm:inline-flex">
                            <span>Search</span>
                            <kbd class="rounded border border-border px-1.5 py-0.5 text-xs">⌘K</kbd>
                        </button>

                        <button type="button" class="cf-surface-interactive p-2" aria-label="Notifications">
                            <x-admin.icon name="bell" class="h-5 w-5" />
                        </button>

                        <button id="admin-theme-toggle" type="button" class="cf-surface-interactive px-2 py-1.5 text-sm" aria-label="Toggle theme">
                            <span data-theme-label>System</span>
                        </button>

                        <div class="relative">
                            <button type="button" data-admin-dropdown-toggle class="cf-surface-interactive border border-border px-2 py-1.5 text-sm" aria-expanded="false">
                                {{ strtoupper(app()->getLocale()) }}
                            </button>
                            <div data-admin-dropdown hidden class="admin-dropdown">
                                @foreach (config('admin.locale.available', ['en' => 'English']) as $code => $label)
                                    <button type="button" class="cf-command-item block w-full px-3 py-2 text-left text-sm" disabled>{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>

                        <div class="relative">
                            <button type="button" data-admin-dropdown-toggle class="cf-surface-interactive flex items-center gap-2 px-2 py-1.5" aria-expanded="false">
                                <span class="hidden text-sm sm:inline">{{ auth()->user()->name ?? 'Admin' }}</span>
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-subtle text-xs font-semibold text-primary-subtle-foreground">
                                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                                </span>
                            </button>
                            <div data-admin-dropdown hidden class="admin-dropdown">
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" class="cf-command-item block w-full px-3 py-2 text-left text-sm">Sign out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                @hasSection('page')
                    @yield('page')
                @else
                    @yield('content')
                @endif
            </main>
        </div>
    </div>

    <x-admin.command-palette />
    @stack('scripts')
</body>
</html>
