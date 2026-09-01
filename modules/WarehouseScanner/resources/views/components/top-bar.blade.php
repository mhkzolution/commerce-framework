@props([
    'staffName' => '',
    'todayScans' => 0,
])

<header class="scanner-topbar" role="banner">
    <div class="scanner-topbar__brand">
        <span class="scanner-topbar__logo" aria-hidden="true">WH</span>
        <div>
            <p class="scanner-topbar__title">{{ __('warehouse::scanner.title') }}</p>
            <p class="scanner-topbar__subtitle" data-scanner-today-scans>
                {{ __('warehouse::scanner.today_scans', ['count' => $todayScans]) }}
            </p>
        </div>
    </div>

    <div class="scanner-topbar__meta">
        <div class="scanner-topbar__staff">
            <span class="scanner-topbar__label">{{ __('warehouse::scanner.staff') }}</span>
            <span class="scanner-topbar__value">{{ $staffName }}</span>
        </div>
        <div class="scanner-topbar__clock">
            <span class="scanner-topbar__value" id="scanner-clock" data-scanner-clock></span>
        </div>
    </div>
</header>
