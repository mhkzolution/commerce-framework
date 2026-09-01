@props([
    'stats' => [],
])

@php
    $tiles = [
        ['key' => 'total_scans', 'label' => __('warehouse::scanner.stats.total_scans')],
        ['key' => 'stock_checks', 'label' => __('warehouse::scanner.stats.stock_checks')],
        ['key' => 'labels_attached', 'label' => __('warehouse::scanner.stats.labels_attached')],
        ['key' => 'receiving', 'label' => __('warehouse::scanner.stats.receiving')],
        ['key' => 'picking', 'label' => __('warehouse::scanner.stats.picking')],
        ['key' => 'packing', 'label' => __('warehouse::scanner.stats.packing')],
        ['key' => 'inventory_counts', 'label' => __('warehouse::scanner.stats.inventory_counts')],
        ['key' => 'transfers', 'label' => __('warehouse::scanner.stats.transfers')],
    ];
@endphp

<div class="scanner-dashboard">
    <div class="scanner-dashboard__grid">
        @foreach ($tiles as $tile)
            <div class="scanner-dashboard__tile">
                <p class="scanner-dashboard__label">{{ $tile['label'] }}</p>
                <p class="scanner-dashboard__value">{{ (int) ($stats[$tile['key']] ?? 0) }}</p>
            </div>
        @endforeach
    </div>
</div>
