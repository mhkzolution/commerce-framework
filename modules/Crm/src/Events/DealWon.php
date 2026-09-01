<?php

declare(strict_types=1);

namespace Commerce\Crm\Events;

use Commerce\Contracts\Event\DomainEventInterface;

final readonly class DealWon implements DomainEventInterface
{
    public function __construct(
        public ?string $leadUuid,
        public int $amount,
        public ?string $dealUuid = null,
        public ?int $tenantId = null,
    ) {}

    public function getEventName(): string
    {
        return 'crm.deal.won';
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
            'lead_uuid' => $this->leadUuid,
            'deal_uuid' => $this->dealUuid,
            'amount' => $this->amount,
            'tenant_id' => $this->tenantId,
        ];
    }
}
