<?php

declare(strict_types=1);

namespace Commerce\Inventory\Services;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Contracts\Inventory\StockLevelInterface;
use Commerce\Contracts\Inventory\StockMovementType;
use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Inventory\DTO\StockLevel;
use Commerce\Inventory\Events\StockAdjusted;
use Commerce\Inventory\Models\InventoryItem;
use Commerce\Inventory\Models\StockMovement;
use Illuminate\Support\Facades\DB;

final class InventoryService extends BaseService implements InventoryServiceInterface
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
        private readonly ProductQueryServiceInterface $productQueryService,
    ) {}

    public function adjust(string $purchasableUuid, int $quantity, ?string $reason = null): StockLevelInterface
    {
        if ($quantity === 0) {
            throw new DomainException('Adjustment quantity cannot be zero.');
        }

        return $this->applyMovement(
            purchasableUuid: $purchasableUuid,
            type: StockMovementType::Adjustment,
            quantity: $quantity,
            reason: $reason,
        );
    }

    public function receive(string $purchasableUuid, int $quantity, ?string $reason = null): StockLevelInterface
    {
        if ($quantity <= 0) {
            throw new DomainException('Receive quantity must be greater than zero.');
        }

        return $this->applyMovement(
            purchasableUuid: $purchasableUuid,
            type: StockMovementType::Receive,
            quantity: $quantity,
            reason: $reason,
        );
    }

    public function setOnHand(string $purchasableUuid, int $onHand, ?string $reason = null): StockLevelInterface
    {
        if ($onHand < 0) {
            throw new DomainException('On-hand quantity cannot be negative.');
        }

        return DB::transaction(function () use ($purchasableUuid, $onHand, $reason): StockLevelInterface {
            $item = $this->ensureItem($purchasableUuid);
            $delta = $onHand - $item->on_hand;

            if ($delta === 0) {
                return $this->toStockLevel($item);
            }

            return $this->recordMovement(
                item: $item,
                type: StockMovementType::Adjustment,
                quantity: $delta,
                reason: $reason ?? 'Set stock level',
            );
        });
    }

    public function sale(
        string $purchasableUuid,
        int $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $reason = null,
    ): StockLevelInterface {
        if ($quantity <= 0) {
            throw new DomainException('Sale quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($purchasableUuid, $quantity, $referenceType, $referenceId, $reason): StockLevelInterface {
            $item = $this->ensureItem($purchasableUuid);

            return $this->recordMovement(
                item: $item,
                type: StockMovementType::Sale,
                quantity: -$quantity,
                reason: $reason ?? 'Order sale',
                referenceType: $referenceType,
                referenceId: $referenceId,
            );
        });
    }

    public function returnStock(
        string $purchasableUuid,
        int $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $reason = null,
    ): StockLevelInterface {
        if ($quantity <= 0) {
            throw new DomainException('Return quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($purchasableUuid, $quantity, $referenceType, $referenceId, $reason): StockLevelInterface {
            $item = $this->ensureItem($purchasableUuid);

            return $this->recordMovement(
                item: $item,
                type: StockMovementType::Return,
                quantity: $quantity,
                reason: $reason ?? 'Order return',
                referenceType: $referenceType,
                referenceId: $referenceId,
            );
        });
    }

    public function reserve(
        string $purchasableUuid,
        int $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $reason = null,
    ): StockLevelInterface {
        if ($quantity <= 0) {
            throw new DomainException('Reserve quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($purchasableUuid, $quantity, $referenceType, $referenceId, $reason): StockLevelInterface {
            $item = $this->ensureItem($purchasableUuid);

            if (($item->on_hand - $item->reserved) < $quantity) {
                throw new DomainException('Insufficient stock to reserve.');
            }

            $item->update(['reserved' => $item->reserved + $quantity]);

            StockMovement::query()->create([
                'inventory_item_id' => $item->id,
                'type' => StockMovementType::Reservation->value,
                'quantity' => $quantity,
                'on_hand_before' => $item->on_hand,
                'on_hand_after' => $item->on_hand,
                'reason' => $reason ?? 'Stock reservation',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            return $this->toStockLevel($item->fresh());
        });
    }

    public function release(
        string $purchasableUuid,
        int $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $reason = null,
    ): StockLevelInterface {
        if ($quantity <= 0) {
            throw new DomainException('Release quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($purchasableUuid, $quantity, $referenceType, $referenceId, $reason): StockLevelInterface {
            $item = $this->ensureItem($purchasableUuid);

            if ($item->reserved < $quantity) {
                throw new DomainException('Cannot release more than reserved stock.');
            }

            $item->update(['reserved' => $item->reserved - $quantity]);

            StockMovement::query()->create([
                'inventory_item_id' => $item->id,
                'type' => StockMovementType::Release->value,
                'quantity' => -$quantity,
                'on_hand_before' => $item->on_hand,
                'on_hand_after' => $item->on_hand,
                'reason' => $reason ?? 'Release reservation',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            return $this->toStockLevel($item->fresh());
        });
    }

    private function applyMovement(
        string $purchasableUuid,
        StockMovementType $type,
        int $quantity,
        ?string $reason,
    ): StockLevelInterface {
        return DB::transaction(function () use ($purchasableUuid, $type, $quantity, $reason): StockLevelInterface {
            $item = $this->ensureItem($purchasableUuid);

            return $this->recordMovement($item, $type, $quantity, $reason);
        });
    }

    private function recordMovement(
        InventoryItem $item,
        StockMovementType $type,
        int $quantity,
        ?string $reason,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): StockLevelInterface {
        $onHandBefore = $item->on_hand;
        $onHandAfter = $onHandBefore + $quantity;

        if ($onHandAfter < 0) {
            throw new DomainException('Insufficient stock for this adjustment.');
        }

        if ($onHandAfter < $item->reserved) {
            throw new DomainException('On-hand quantity cannot be lower than reserved stock.');
        }

        $item->update(['on_hand' => $onHandAfter]);

        StockMovement::query()->create([
            'inventory_item_id' => $item->id,
            'type' => $type->value,
            'quantity' => $quantity,
            'on_hand_before' => $onHandBefore,
            'on_hand_after' => $onHandAfter,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);

        $this->eventBus->dispatch(new StockAdjusted(
            purchasableUuid: $item->purchasable_uuid,
            movementType: $type->value,
            quantity: $quantity,
            onHandBefore: $onHandBefore,
            onHandAfter: $onHandAfter,
            tenantId: $item->tenant_id,
        ));

        return $this->toStockLevel($item->fresh());
    }

    private function ensureItem(string $purchasableUuid): InventoryItem
    {
        $variant = $this->productQueryService->findVariantByUuid($purchasableUuid);

        if ($variant === null) {
            throw new EntityNotFoundException("Purchasable variant [{$purchasableUuid}] not found.");
        }

        return InventoryItem::query()->firstOrCreate(
            ['purchasable_uuid' => $purchasableUuid],
            ['on_hand' => 0, 'reserved' => 0],
        );
    }

    private function toStockLevel(InventoryItem $item): StockLevel
    {
        return new StockLevel(
            purchasableUuid: $item->purchasable_uuid,
            onHand: $item->on_hand,
            reserved: $item->reserved,
        );
    }
}
