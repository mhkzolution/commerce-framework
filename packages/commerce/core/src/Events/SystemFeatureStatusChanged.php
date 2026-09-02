<?php

declare(strict_types=1);

namespace Commerce\Core\Events;

use Commerce\Contracts\Event\DomainEventInterface;
use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Models\SystemFeature;
use DateTimeImmutable;

final readonly class SystemFeatureStatusChanged implements DomainEventInterface
{
    public function __construct(
        public SystemFeature $feature,
        public FeatureStatus $oldStatus,
        public FeatureStatus $newStatus,
        public ?string $moduleName,
        public ?int $userId = null,
        public ?int $tenantId = null,
    ) {}

    public function getEventName(): string
    {
        return 'system.feature.status_changed';
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'feature_id' => $this->feature->id,
            'code' => $this->feature->code,
            'feature_name' => $this->feature->name,
            'module_code' => $this->feature->module_code,
            'module_name' => $this->moduleName,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
        ];
    }
}
