<?php

declare(strict_types=1);

namespace Commerce\Customers\Events;

use Commerce\Contracts\Event\DomainEventInterface;

final readonly class CustomerCreated implements DomainEventInterface
{
    public function __construct(
        public string $customerUuid,
        public string $email,
        public ?int $tenantId = null,
    ) {}

    public function getEventName(): string
    {
        return 'customer.created';
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
            'email' => $this->email,
            'tenant_id' => $this->tenantId,
        ];
    }
}
