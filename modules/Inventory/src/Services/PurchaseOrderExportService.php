<?php

declare(strict_types=1);

namespace Commerce\Inventory\Services;

use Commerce\Inventory\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PurchaseOrderExportService
{
    /**
     * @return Builder<PurchaseOrder>
     */
    public function filteredQuery(?int $supplierId = null, ?string $status = null): Builder
    {
        return PurchaseOrder::query()
            ->with(['supplier', 'lines'])
            ->when($supplierId, static fn (Builder $query) => $query->where('supplier_id', $supplierId))
            ->when($status !== null && $status !== '', static fn (Builder $query) => $query->where('status', $status))
            ->latest();
    }

    public function exportCsv(?int $supplierId = null, ?string $status = null): StreamedResponse
    {
        $orders = $this->filteredQuery($supplierId, $status)->get();
        $filename = 'purchase-orders-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($orders): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, [
                'Reference',
                'Supplier',
                'Status',
                'Expected Date',
                'Lines',
                'Qty Ordered',
                'Qty Received',
                'Qty Incoming',
                'Value Ordered',
                'Value Received',
                'Created At',
            ]);

            foreach ($orders as $order) {
                $qtyOrdered = $order->lines->sum('quantity_ordered');
                $qtyReceived = $order->lines->sum('quantity_received');
                $qtyIncoming = $order->lines->sum(static fn ($line) => $line->incomingQuantity());
                $valueOrdered = $order->lines->sum(static fn ($line) => $line->orderedValue());
                $valueReceived = $order->lines->sum(static fn ($line) => $line->receivedValue());

                fputcsv($handle, [
                    $order->reference,
                    $order->supplier?->name ?? $order->supplier_name ?? '',
                    $order->status,
                    $order->expected_at?->format('Y-m-d') ?? '',
                    $order->lines->count(),
                    $qtyOrdered,
                    $qtyReceived,
                    $qtyIncoming,
                    number_format($valueOrdered, 2, '.', ''),
                    number_format($valueReceived, 2, '.', ''),
                    $order->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
