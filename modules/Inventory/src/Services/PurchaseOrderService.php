<?php

declare(strict_types=1);

namespace Commerce\Inventory\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Inventory\Models\PurchaseOrder;
use Commerce\Inventory\Models\PurchaseOrderLine;
use Commerce\Inventory\Support\PurchaseOrderMoney;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

final class PurchaseOrderService extends BaseService
{
    public function __construct(
        private readonly InventoryServiceInterface $inventoryService,
        private readonly PurchaseOrderMoney $money,
    ) {}

    /**
     * @param  list<array{sku: string, quantity: int, unit_cost?: float|string|null}>  $lines
     */
    public function create(
        string $reference,
        array $lines,
        ?string $supplierName = null,
        ?string $expectedAt = null,
        ?string $notes = null,
        ?int $supplierId = null,
        ?string $currency = null,
    ): PurchaseOrder {
        if ($lines === []) {
            throw new DomainException('Purchase order requires at least one line.');
        }

        return DB::transaction(function () use ($reference, $lines, $supplierName, $expectedAt, $notes, $supplierId, $currency): PurchaseOrder {
            $order = PurchaseOrder::query()->create([
                'reference' => $reference,
                'status' => PurchaseOrder::STATUS_ORDERED,
                'supplier_id' => $supplierId,
                'supplier_name' => $supplierName,
                'currency' => strtoupper($currency ?? $this->money->defaultCurrency()),
                'expected_at' => $expectedAt,
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $variant = ProductVariant::query()->where('sku', $line['sku'])->first();

                if ($variant === null) {
                    throw new DomainException("SKU [{$line['sku']}] not found.");
                }

                PurchaseOrderLine::query()->create([
                    'purchase_order_id' => $order->id,
                    'purchasable_uuid' => $variant->uuid,
                    'sku' => $variant->sku,
                    'unit_cost' => $this->resolveUnitCost($line, $variant),
                    'quantity_ordered' => max(1, (int) $line['quantity']),
                ]);
            }

            return $order->fresh(['lines']);
        });
    }

    public function receiveLine(int $lineId, int $quantity): PurchaseOrderLine
    {
        return DB::transaction(function () use ($lineId, $quantity): PurchaseOrderLine {
            $line = PurchaseOrderLine::query()->with('purchaseOrder')->find($lineId);

            if ($line === null) {
                throw new EntityNotFoundException("Purchase order line [{$lineId}] not found.");
            }

            if (! $line->purchaseOrder?->isOpen()) {
                throw new DomainException('Purchase order is not open for receiving.');
            }

            $remaining = $line->incomingQuantity();

            if ($quantity <= 0 || $quantity > $remaining) {
                throw new DomainException("Invalid receive quantity. Remaining: {$remaining}.");
            }

            $this->inventoryService->receive(
                $line->purchasable_uuid,
                $quantity,
                reason: 'PO '.$line->purchaseOrder->reference,
            );

            $line->update([
                'quantity_received' => $line->quantity_received + $quantity,
            ]);

            $this->syncOrderStatus($line->purchaseOrder->fresh(['lines']));

            return $line->fresh();
        });
    }

    public function cancelLine(int $lineId): PurchaseOrderLine
    {
        return DB::transaction(function () use ($lineId): PurchaseOrderLine {
            $line = PurchaseOrderLine::query()->with('purchaseOrder')->find($lineId);

            if ($line === null) {
                throw new EntityNotFoundException("Purchase order line [{$lineId}] not found.");
            }

            if (! $line->purchaseOrder?->isOpen()) {
                throw new DomainException('Purchase order is not open for changes.');
            }

            if ($line->incomingQuantity() <= 0) {
                throw new DomainException('Purchase order line has no remaining incoming quantity.');
            }

            $line->update([
                'quantity_ordered' => $line->quantity_received,
            ]);

            $this->syncOrderStatus($line->purchaseOrder->fresh(['lines']));

            return $line->fresh();
        });
    }

    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        if (! $order->isOpen()) {
            throw new DomainException('Purchase order is not open for cancellation.');
        }

        $order->update([
            'status' => PurchaseOrder::STATUS_CANCELLED,
        ]);

        return $order->fresh(['lines']);
    }

    private function syncOrderStatus(PurchaseOrder $order): void
    {
        $hasRemaining = $order->lines->contains(
            static fn (PurchaseOrderLine $line): bool => $line->incomingQuantity() > 0,
        );

        $order->update([
            'status' => $hasRemaining ? PurchaseOrder::STATUS_PARTIAL : PurchaseOrder::STATUS_RECEIVED,
        ]);
    }

    /**
     * @param  array{sku: string, quantity: int, unit_cost?: float|string|null}  $line
     */
    private function resolveUnitCost(array $line, ProductVariant $variant): ?float
    {
        if (array_key_exists('unit_cost', $line) && $line['unit_cost'] !== null && $line['unit_cost'] !== '') {
            return max(0, (float) $line['unit_cost']);
        }

        return $variant->cost !== null ? (float) $variant->cost : null;
    }
}
