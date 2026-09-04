@extends('warehouse::layouts.scanner')

@section('title', __('warehouse::scanner.dashboard'))

@section('content')
    <div class="scanner-layout">
        <x-warehouse::mode-rail active="dashboard" />

        <div class="scanner-main">
            <x-warehouse::top-bar
                :staff-name="auth()->user()?->name ?? ''"
                :today-scans="$stats['total_scans'] ?? 0"
            />

            <div class="scanner-page">
                <header class="scanner-page__header">
                    <h1 class="scanner-page__title">{{ __('warehouse::scanner.dashboard') }}</h1>
                    <a href="{{ route('warehouse.index') }}" class="scanner-page__back">
                        ← {{ __('warehouse::scanner.title') }}
                    </a>
                </header>

                <x-warehouse::warehouse-dashboard :stats="$stats" />

                <div class="scanner-page__section">
                    <x-warehouse::scan-history :scans="$recentScans" />
                </div>
            </div>
        </div>
    </div>
@endsection
