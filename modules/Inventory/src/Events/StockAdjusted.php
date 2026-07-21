<?php

declare(strict_types=1);

namespace Commerce\Inventory\Events;

use Commerce\Contracts\Event\DomainEventInterface;

final readonly class StockAdjusted implements DomainEventInterface
{
    public function __construct(
        public string $purchasableUuid,
        public string $movementType,
        public int $quantity,
        public int $onHandBefore,
        public int $onHandAfter,
        public ?int $tenantId = null,
    ) {}

    public function getEventName(): string
    {
        return 'inventory.stock_adjusted';
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function toPayload(): array
    {
        return [
            'purchasable_uuid' => $this->purchasableUuid,
            'movement_type' => $this->movementType,
            'quantity' => $this->quantity,
            'on_hand_before' => $this->onHandBefore,
            'on_hand_after' => $this->onHandAfter,
            'tenant_id' => $this->tenantId,
        ];
    }
}
