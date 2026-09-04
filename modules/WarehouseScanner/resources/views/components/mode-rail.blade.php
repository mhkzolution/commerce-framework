@props([
    'active' => 'scanner',
])

<nav class="scanner-nav-rail" aria-label="{{ __('warehouse::scanner.title') }}">
    <div class="scanner-nav-rail__brand" title="{{ __('warehouse::scanner.title') }}">WH</div>

    <a
        href="{{ route('warehouse.index') }}"
        class="scanner-nav-rail__item {{ $active === 'scanner' ? 'is-active' : '' }}"
        title="{{ __('warehouse::scanner.title') }}"
        @if ($active === 'scanner') aria-current="page" @endif
    >
        <x-admin.icon name="qr-code" class="h-5 w-5" />
        <span>{{ __('warehouse::scanner.title') }}</span>
    </a>

    @can('warehouse.reports')
        @if (feature('warehouse-reports'))
        <a
            href="{{ route('warehouse.dashboard') }}"
            class="scanner-nav-rail__item {{ $active === 'dashboard' ? 'is-active' : '' }}"
            title="{{ __('warehouse::scanner.dashboard') }}"
            @if ($active === 'dashboard') aria-current="page" @endif
        >
            <x-admin.icon name="chart-bar" class="h-5 w-5" />
            <span>{{ __('warehouse::scanner.dashboard') }}</span>
        </a>

        <a
            href="{{ route('warehouse.history') }}"
            class="scanner-nav-rail__item {{ $active === 'history' ? 'is-active' : '' }}"
            title="{{ __('warehouse::scanner.history') }}"
            @if ($active === 'history') aria-current="page" @endif
        >
            <x-admin.icon name="clock" class="h-5 w-5" />
            <span>{{ __('warehouse::scanner.history') }}</span>
        </a>
        @endif
    @endcan

    <div class="scanner-nav-rail__spacer"></div>

    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="scanner-nav-rail__item" title="{{ __('warehouse::scanner.logout') }}">
            <x-admin.icon name="x-mark" class="h-5 w-5" />
            <span>{{ __('warehouse::scanner.logout') }}</span>
        </button>
    </form>
</nav>
