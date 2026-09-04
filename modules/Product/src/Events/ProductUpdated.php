<?php

declare(strict_types=1);

namespace Commerce\Product\Events;

use Commerce\Contracts\Event\DomainEventInterface;

final readonly class ProductUpdated implements DomainEventInterface
{
    public function __construct(
        public string $productUuid,
        public ?int $tenantId = null,
    ) {}

    public function getEventName(): string
    {
        return 'product.updated';
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
            'product_uuid' => $this->productUuid,
            'tenant_id' => $this->tenantId,
        ];
    }
}
