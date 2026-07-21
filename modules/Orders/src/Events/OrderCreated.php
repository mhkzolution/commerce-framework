<?php

declare(strict_types=1);

namespace Commerce\Orders\Events;

use Commerce\Contracts\Event\DomainEventInterface;

final readonly class OrderCreated implements DomainEventInterface
{
    public function __construct(
        public string $orderUuid,
        public string $orderNumber,
        public ?int $tenantId = null,
    ) {}

    public function getEventName(): string
    {
        return 'order.created';
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
            'order_uuid' => $this->orderUuid,
            'order_number' => $this->orderNumber,
            'tenant_id' => $this->tenantId,
        ];
    }
}
