<?php

declare(strict_types=1);

namespace Commerce\Inventory\Services;

use Commerce\Inventory\Models\PurchaseOrder;
use Commerce\Inventory\Models\PurchaseOrderLine;
use Illuminate\Support\Facades\DB;

final class IncomingStockQueryService
{
    /**
     * @param  list<string>  $purchasableUuids
     * @return array<string, int>
     */
    public function incomingForPurchasables(array $purchasableUuids): array
    {
        if ($purchasableUuids === []) {
            return [];
        }

        return PurchaseOrderLine::query()
            ->select('purchasable_uuid', DB::raw('SUM(quantity_ordered - quantity_received) as incoming'))
            ->whereIn('purchasable_uuid', $purchasableUuids)
            ->whereHas('purchaseOrder', static function ($query): void {
                $query->whereIn('status', [
                    PurchaseOrder::STATUS_ORDERED,
                    PurchaseOrder::STATUS_PARTIAL,
                ]);
            })
            ->groupBy('purchasable_uuid')
            ->pluck('incoming', 'purchasable_uuid')
            ->map(static fn ($value) => max(0, (int) $value))
            ->all();
    }
}
