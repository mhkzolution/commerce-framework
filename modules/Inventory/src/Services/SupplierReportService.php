<?php

declare(strict_types=1);

namespace Commerce\Inventory\Services;

use Commerce\Inventory\Models\PurchaseOrder;
use Commerce\Inventory\Models\Supplier;
use Commerce\Inventory\Support\PurchaseOrderMoney;
use Commerce\Inventory\Support\SupplierReportDateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SupplierReportService
{
    public function __construct(
        private readonly PurchaseOrderMoney $money,
    ) {}

    /**
     * @return array{
     *     total_orders: int,
     *     open_orders: int,
     *     total_incoming: int,
     *     total_received: int,
     *     total_value_ordered: float,
     *     total_value_received: float,
     *     recent_orders: Collection<int, PurchaseOrder>
     * }
     */
    public function summary(Supplier $supplier, ?SupplierReportDateRange $range = null): array
    {
        $range ??= SupplierReportDateRange::fromRequest();
        $orders = $this->ordersQuery($supplier, $range)->get();

        $openStatuses = [PurchaseOrder::STATUS_ORDERED, PurchaseOrder::STATUS_PARTIAL];

        return [
            'total_orders' => $orders->count(),
            'open_orders' => $orders->whereIn('status', $openStatuses)->count(),
            'total_incoming' => $orders
                ->whereIn('status', $openStatuses)
                ->sum(static fn (PurchaseOrder $order) => $order->lines->sum(static fn ($line) => $line->incomingQuantity())),
            'total_received' => $orders->sum(static fn (PurchaseOrder $order) => $order->lines->sum('quantity_received')),
            'total_value_ordered' => $orders->sum(fn (PurchaseOrder $order) => $order->lines->sum(
                fn ($line) => $this->money->toBase($line->orderedValue(), $order->currency),
            )),
            'total_value_received' => $orders->sum(fn (PurchaseOrder $order) => $order->lines->sum(
                fn ($line) => $this->money->toBase($line->receivedValue(), $order->currency),
            )),
            'currency' => $this->money->defaultCurrency(),
            'recent_orders' => $orders->take(10),
        ];
    }

    public function exportCsv(Supplier $supplier, ?SupplierReportDateRange $range = null): StreamedResponse
    {
        $range ??= SupplierReportDateRange::fromRequest();
        $orders = $this->ordersQuery($supplier, $range)->get();

        $filename = 'supplier-'.str($supplier->name)->slug().'-purchase-orders.csv';

        return response()->streamDownload(function () use ($orders): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Reference', 'Status', 'Expected', 'SKU', 'Unit Cost', 'Ordered', 'Received', 'Incoming', 'Line Total', 'Created At']);

            foreach ($orders as $order) {
                foreach ($order->lines as $line) {
                    fputcsv($handle, [
                        $order->reference,
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
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    public function monthlyOrderSeries(Supplier $supplier, ?SupplierReportDateRange $range = null): array
    {
        $range ??= SupplierReportDateRange::fromRequest();

        $counts = $this->ordersQuery($supplier, $range)
            ->get()
            ->groupBy(static fn (PurchaseOrder $order): string => $order->created_at?->format('Y-m') ?? '')
            ->map(static fn ($orders): int => $orders->count());

        if ($counts->isEmpty()) {
            return [];
        }

        $series = [];
        $cursor = $range->from->copy()->startOfMonth();
        $end = $range->to->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->format('Y-m');
            $series[] = [
                'label' => $cursor->format('M Y'),
                'count' => (int) ($counts->get($key) ?? 0),
            ];
            $cursor->addMonth();
        }

        return $series;
    }

    /**
     * @return list<array{label: string, ordered: int, received: int}>
     */
    public function monthlyUnitsSeries(Supplier $supplier, ?SupplierReportDateRange $range = null): array
    {
        $range ??= SupplierReportDateRange::fromRequest();
        $orders = $this->ordersQuery($supplier, $range)->get();

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

    private function ordersQuery(Supplier $supplier, SupplierReportDateRange $range): Builder
    {
        return PurchaseOrder::query()
            ->with('lines')
            ->where('supplier_id', $supplier->id)
            ->whereBetween('created_at', [$range->from, $range->to])
            ->latest();
    }
}
