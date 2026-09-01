@props([
    'scans' => [],
    'compact' => false,
])

<div class="scanner-history {{ $compact ? 'scanner-history--compact' : '' }}" data-scanner-history>
    <div class="scanner-history__header">
        <h2 class="scanner-history__title">{{ __('warehouse::scanner.history') }}</h2>
        @if ($compact)
            <button type="button" class="scanner-history__toggle" data-scanner-history-toggle aria-expanded="false">
                {{ __('warehouse::scanner.history') }}
            </button>
        @endif
    </div>

    <div class="scanner-history__table-wrap" @if($compact) hidden data-scanner-history-body @endif>
        <table class="scanner-history__table">
            <thead>
                <tr>
                    <th>{{ __('warehouse::scanner.date') }}</th>
                    <th>{{ __('warehouse::scanner.staff') }}</th>
                    <th>{{ __('warehouse::scanner.mode') }}</th>
                    <th>{{ __('warehouse::scanner.sku') }}</th>
                    <th>{{ __('warehouse::scanner.action') }}</th>
                </tr>
            </thead>
            <tbody data-scanner-history-rows>
                @forelse ($scans as $scan)
                    <tr>
                        <td>{{ isset($scan['created_at']) ? \Illuminate\Support\Carbon::parse($scan['created_at'])->format('H:i') : '—' }}</td>
                        <td>{{ $scan['staff'] ?? '—' }}</td>
                        <td>{{ __('warehouse::scanner.modes.' . ($scan['mode'] ?? 'stock-check')) }}</td>
                        <td><code>{{ $scan['sku'] ?? '—' }}</code></td>
                        <td>{{ __('warehouse::scanner.actions.' . ($scan['action'] ?? 'found'), [], null, false) !== 'warehouse::scanner.actions.' . ($scan['action'] ?? 'found') ? __('warehouse::scanner.actions.' . $scan['action']) : ($scan['action'] ?? '—') }}</td>
                    </tr>
                @empty
                    <tr data-scanner-history-empty>
                        <td colspan="5" class="scanner-history__empty">—</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
