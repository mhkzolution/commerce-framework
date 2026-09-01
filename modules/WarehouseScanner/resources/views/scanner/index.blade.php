@extends('warehouse::layouts.scanner')

@section('title', __('warehouse::scanner.title'))

@section('content')
    <div
        class="scanner-layout"
        id="warehouse-scanner-app"
        data-scanner-app
        data-scanner-config='@json($scannerConfig)'
        data-scanner-initial='@json([
            'mode' => $activeMode,
            'recentScans' => $recentScans,
        ])'
    >
        <x-warehouse::mode-rail active="scanner" />

        <div class="scanner-main">
            <x-warehouse::top-bar
                :staff-name="auth()->user()?->name ?? ''"
                :today-scans="$todayScans"
            />

            <div id="scanner-toast" class="scanner-toast hidden" role="alert" data-scanner-toast></div>

            <div class="scanner-workspace">
                <aside class="scanner-workspace__modes scanner-workspace__modes--desktop">
                    <x-warehouse::mode-switcher
                        :active-mode="$activeMode"
                        :modes="config('warehouse-scanner.modes', [])"
                    />
                </aside>

                <div class="scanner-workspace__center">
                    <div class="scanner-workspace__modes scanner-workspace__modes--mobile">
                        <x-warehouse::mode-switcher
                            :active-mode="$activeMode"
                            :modes="config('warehouse-scanner.modes', [])"
                            :mobile="true"
                        />
                    </div>

                    <x-warehouse::scanner-input />

                    <x-warehouse::product-preview />
                </div>

                <aside class="scanner-workspace__actions">
                    <x-warehouse::scanner-action-panel />
                </aside>
            </div>

            <div class="scanner-workspace__history">
                <x-warehouse::scan-history :scans="$recentScans" :compact="true" />
            </div>

            <x-warehouse::shortcut-bar />
        </div>
    </div>
@endsection
