<?php

declare(strict_types=1);

namespace Commerce\WarehouseScanner\Services;

use Commerce\WarehouseScanner\Models\WarehouseScan;
use Illuminate\Support\Carbon;

final class ScannerDashboardService
{
    /**
     * @return array{
     *     total_scans: int,
     *     stock_checks: int,
     *     labels_attached: int,
     *     receiving: int,
     *     picking: int,
     *     packing: int,
     *     inventory_counts: int,
     *     transfers: int
     * }
     */
    public function todayStats(): array
    {
        $start = Carbon::today();
        $end = Carbon::tomorrow();

        $counts = WarehouseScan::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('mode, count(*) as total')
            ->groupBy('mode')
            ->pluck('total', 'mode');

        return [
            'total_scans' => (int) $counts->sum(),
            'stock_checks' => (int) ($counts['stock-check'] ?? 0),
            'labels_attached' => (int) ($counts['label-attachment'] ?? 0),
            'receiving' => (int) ($counts['receiving'] ?? 0),
            'picking' => (int) ($counts['picking'] ?? 0),
            'packing' => (int) ($counts['packing'] ?? 0),
            'inventory_counts' => (int) ($counts['inventory-count'] ?? 0),
            'transfers' => (int) ($counts['transfer'] ?? 0),
        ];
    }
}
