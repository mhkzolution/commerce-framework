<?php

declare(strict_types=1);

namespace Commerce\Contracts\Event;

interface DomainEventInterface
{
    public function getEventName(): string;

    public function getOccurredAt(): \DateTimeImmutable;

    public function getTenantId(): ?int;

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array;
}
