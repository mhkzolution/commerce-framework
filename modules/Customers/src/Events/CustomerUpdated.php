<?php

declare(strict_types=1);

namespace Commerce\Customers\Events;

use Commerce\Contracts\Event\DomainEventInterface;

final readonly class CustomerUpdated implements DomainEventInterface
{
    public function __construct(
        public string $customerUuid,
        public ?int $tenantId = null,
    ) {}

    public function getEventName(): string
    {
        return 'customer.updated';
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
            'customer_uuid' => $this->customerUuid,
            'tenant_id' => $this->tenantId,
        ];
    }
}
