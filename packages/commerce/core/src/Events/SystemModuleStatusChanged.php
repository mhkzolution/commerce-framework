<?php

declare(strict_types=1);

namespace Commerce\Core\Events;

use Commerce\Contracts\Event\DomainEventInterface;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Models\SystemModule;
use DateTimeImmutable;

final readonly class SystemModuleStatusChanged implements DomainEventInterface
{
    public function __construct(
        public SystemModule $module,
        public ModuleStatus $oldStatus,
        public ModuleStatus $newStatus,
        public ?int $userId = null,
        public ?int $tenantId = null,
    ) {}

    public function getEventName(): string
    {
        return 'system.module.status_changed';
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
            'module_id' => $this->module->id,
            'code' => $this->module->code,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
        ];
    }
}
