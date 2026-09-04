@props([
    'activeMode' => 'stock-check',
    'modes' => [],
    'mobile' => false,
])

<nav
    class="scanner-mode-switcher {{ $mobile ? 'scanner-mode-switcher--mobile' : 'scanner-mode-switcher--rail' }}"
    aria-label="{{ __('warehouse::scanner.mode') }}"
    data-scanner-mode-switcher
>
    @if ($mobile)
        <label class="scanner-mode-switcher__mobile-label" for="scanner-mode-select">{{ __('warehouse::scanner.mode') }}</label>
        <select id="scanner-mode-select" class="scanner-mode-switcher__select" data-scanner-mode-select>
            @foreach ($modes as $key => $mode)
                <option value="{{ $key }}" @selected($activeMode === $key)>
                    {{ __('warehouse::scanner.modes.' . $key) }}
                </option>
            @endforeach
        </select>
    @else
        @foreach ($modes as $key => $mode)
            <button
                type="button"
                class="scanner-mode-switcher__item {{ $activeMode === $key ? 'is-active' : '' }}"
                data-scanner-mode="{{ $key }}"
                title="{{ $mode['shortcut'] ?? '' }}"
            >
                <span class="scanner-mode-switcher__label">{{ __('warehouse::scanner.modes.' . $key) }}</span>
                @if (! empty($mode['shortcut']))
                    <span class="scanner-mode-switcher__shortcut"><kbd>{{ $mode['shortcut'] }}</kbd></span>
                @endif
            </button>
        @endforeach

        <div class="scanner-mode-switcher__spacer"></div>

        <a
            href="{{ route('warehouse.dashboard') }}"
            class="scanner-mode-switcher__item scanner-mode-switcher__item--dashboard"
            title="F8"
        >
            <x-admin.icon name="chart-bar" class="h-5 w-5" />
            <span class="scanner-mode-switcher__label">{{ __('warehouse::scanner.dashboard') }}</span>
            <span class="scanner-mode-switcher__shortcut"><kbd>F8</kbd></span>
        </a>
    @endif
</nav>
