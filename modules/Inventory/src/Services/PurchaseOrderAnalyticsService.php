<?php

declare(strict_types=1);

namespace Commerce\Inventory\Services;

use Commerce\Inventory\Models\PurchaseOrder;
use Commerce\Inventory\Support\PurchaseOrderMoney;
use Commerce\Inventory\Support\SupplierReportDateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PurchaseOrderAnalyticsService
{
    public function __construct(
        private readonly PurchaseOrderMoney $money,
    ) {}

    /**
     * @return array{
     *     total_orders: int,
     *     open_orders: int,
     *     received_orders: int,
     *     cancelled_orders: int,
     *     total_units_ordered: int,
     *     total_units_received: int,
     *     total_value_ordered: float,
     *     total_value_received: float,
     *     from: string,
     *     to: string,
     *     preset: string
     * }
     */
    public function summary(?SupplierReportDateRange $range = null): array
    {
        $range ??= SupplierReportDateRange::fromRequest();
        $orders = $this->ordersQuery($range)->with('lines')->get();

        return [
            'total_orders' => $orders->count(),
            'open_orders' => $orders->whereIn('status', [PurchaseOrder::STATUS_ORDERED, PurchaseOrder::STATUS_PARTIAL])->count(),
            'received_orders' => $orders->where('status', PurchaseOrder::STATUS_RECEIVED)->count(),
            'cancelled_orders' => $orders->where('status', PurchaseOrder::STATUS_CANCELLED)->count(),
            'total_units_ordered' => $orders->sum(static fn (PurchaseOrder $order) => $order->lines->sum('quantity_ordered')),
            'total_units_received' => $orders->sum(static fn (PurchaseOrder $order) => $order->lines->sum('quantity_received')),
            'total_value_ordered' => $orders->sum(fn (PurchaseOrder $order) => $order->lines->sum(
                fn ($line) => $this->money->toBase($line->orderedValue(), $order->currency),
            )),
            'total_value_received' => $orders->sum(fn (PurchaseOrder $order) => $order->lines->sum(
                fn ($line) => $this->money->toBase($line->receivedValue(), $order->currency),
            )),
            'currency' => $this->money->defaultCurrency(),
            'from' => $range->from->toDateString(),
            'to' => $range->to->toDateString(),
            'preset' => $range->preset,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function ordersByStatus(?SupplierReportDateRange $range = null): array
    {
        $range ??= SupplierReportDateRange::fromRequest();

        return $this->ordersQuery($range)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(static fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * @return list<array{date: string, label: string, count: int}>
     */
    public function ordersSeries(?SupplierReportDateRange $range = null): array
    {
        $range ??= SupplierReportDateRange::fromRequest();

        $rows = $this->ordersQuery($range)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $series = [];
        $cursor = $range->from->copy()->startOfDay();
        $end = $range->to->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->toDateString();
            $series[] = [
                'date' => $key,
                'label' => $cursor->format('M j'),
                'count' => (int) ($rows->get($key)?->count ?? 0),
            ];
            $cursor->addDay();
        }

        return $series;
    }

    /**
     * @return list<array{label: string, ordered: int, received: int}>
     */
    public function monthlyUnitsSeries(?SupplierReportDateRange $range = null): array
    {
        $range ??= SupplierReportDateRange::fromRequest();

        $orders = $this->ordersQuery($range)->with('lines')->get();

        if ($orders->isEmpty()) {
            return [];
        }

        $grouped = $orders->groupBy(static fn (PurchaseOrder $order): string => $order->created_at?->format('Y-m') ?? '');

        $series = [];
        $cursor = $range->from->copy()->startOfMonth();
        $end = $range->to->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->format('Y-m');
            $monthOrders = $grouped->get($key, collect());

            $series[] = [
                'label' => $cursor->format('M Y'),
                'ordered' => (int) $monthOrders->sum(static fn (PurchaseOrder $order) => $order->lines->sum('quantity_ordered')),
                'received' => (int) $monthOrders->sum(static fn (PurchaseOrder $order) => $order->lines->sum('quantity_received')),
            ];
            $cursor->addMonth();
        }

        return $series;
    }

    /**
     * @return Collection<int, object{supplier_name: string, total: int}>
     */
    public function topSuppliers(?SupplierReportDateRange $range = null, int $limit = 10): Collection
    {
        $range ??= SupplierReportDateRange::fromRequest();

        return PurchaseOrder::query()
            ->whereBetween('purchase_orders.created_at', [$range->from, $range->to])
            ->selectRaw('COALESCE(suppliers.name, purchase_orders.supplier_name, ?) as supplier_name, COUNT(*) as total', ['Unassigned'])
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->groupBy('supplier_name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    public function exportCsv(?SupplierReportDateRange $range = null): StreamedResponse
    {
        $range ??= SupplierReportDateRange::fromRequest();
        $orders = $this->ordersQuery($range)->with(['lines', 'supplier'])->get();

        return response()->streamDownload(function () use ($orders): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Reference', 'Supplier', 'Status', 'Expected', 'SKU', 'Unit Cost', 'Ordered', 'Received', 'Incoming', 'Line Total', 'Created At']);

            foreach ($orders as $order) {
                $supplierName = $order->supplier?->name ?? $order->supplier_name ?? '';

                foreach ($order->lines as $line) {
                    fputcsv($handle, [
                        $order->reference,
                        $supplierName,
                        $order->status,
                        $order->expected_at?->format('Y-m-d') ?? '',
                        $line->sku,
                        $line->unit_cost,
                        $line->quantity_ordered,
                        $line->quantity_received,
                        $line->incomingQuantity(),
                        $line->orderedValue(),
                        $order->created_at?->toDateTimeString(),
                    ]);
                }
            }

            fclose($handle);
        }, 'purchase-orders-analytics.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function ordersQuery(SupplierReportDateRange $range): Builder
    {
        return PurchaseOrder::query()
            ->whereBetween('purchase_orders.created_at', [$range->from, $range->to]);
    }
}
