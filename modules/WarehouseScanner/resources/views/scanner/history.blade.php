@extends('warehouse::layouts.scanner')

@section('title', __('warehouse::scanner.history'))

@section('content')
    <div class="scanner-layout">
        <x-warehouse::mode-rail active="history" />

        <div class="scanner-main">
            <x-warehouse::top-bar
                :staff-name="auth()->user()?->name ?? ''"
                :today-scans="0"
            />

            <div class="scanner-page">
                <header class="scanner-page__header">
                    <h1 class="scanner-page__title">{{ __('warehouse::scanner.history') }}</h1>
                    <a href="{{ route('warehouse.index') }}" class="scanner-page__back">
                        ← {{ __('warehouse::scanner.title') }}
                    </a>
                </header>

                <div class="scanner-history scanner-history--page">
                    <div class="scanner-history__table-wrap">
                        <table class="scanner-history__table">
                            <thead>
                                <tr>
                                    <th>{{ __('warehouse::scanner.date') }}</th>
                                    <th>{{ __('warehouse::scanner.staff') }}</th>
                                    <th>{{ __('warehouse::scanner.mode') }}</th>
                                    <th>{{ __('warehouse::scanner.sku') }}</th>
                                    <th>{{ __('warehouse::scanner.action') }}</th>
                                    <th>{{ __('warehouse::scanner.device') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($scans as $scan)
                                    <tr>
                                        <td>{{ $scan->created_at?->format('Y-m-d H:i') }}</td>
                                        <td>{{ $scan->user?->name ?? '—' }}</td>
                                        <td>{{ __('warehouse::scanner.modes.' . $scan->mode) }}</td>
                                        <td><code>{{ $scan->sku }}</code></td>
                                        <td>{{ __('warehouse::scanner.actions.' . $scan->action, [], null, false) !== 'warehouse::scanner.actions.' . $scan->action ? __('warehouse::scanner.actions.' . $scan->action) : $scan->action }}</td>
                                        <td class="scanner-history__device">{{ \Illuminate\Support\Str::limit($scan->meta['device'] ?? '—', 40) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="scanner-history__empty">—</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="scanner-history__pagination">
                        {{ $scans->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
