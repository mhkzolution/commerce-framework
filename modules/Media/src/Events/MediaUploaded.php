<?php

declare(strict_types=1);

namespace Commerce\Media\Events;

use Commerce\Contracts\Event\DomainEventInterface;

final readonly class MediaUploaded implements DomainEventInterface
{
    public function __construct(
        public string $mediaUuid,
        public string $mimeType,
        public int $size,
        public ?int $tenantId = null,
    ) {}

    public function getEventName(): string
    {
        return 'media.uploaded';
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
            'media_uuid' => $this->mediaUuid,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'tenant_id' => $this->tenantId,
        ];
    }
}
