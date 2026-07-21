<?php

declare(strict_types=1);

namespace Commerce\Settings\Events;

use Commerce\Contracts\Event\DomainEventInterface;

final readonly class SettingsGroupUpdated implements DomainEventInterface
{
    /**
     * @param  list<string>  $keys
     */
    public function __construct(
        public string $group,
        public array $keys,
        public ?int $tenantId = null,
    ) {}

    public function getEventName(): string
    {
        return 'settings.group.updated';
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
            'group' => $this->group,
            'keys' => $this->keys,
            'tenant_id' => $this->tenantId,
        ];
    }
}
