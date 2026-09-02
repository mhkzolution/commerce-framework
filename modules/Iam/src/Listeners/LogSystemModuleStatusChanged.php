<?php

declare(strict_types=1);

namespace Commerce\Iam\Listeners;

use Commerce\Core\Events\SystemModuleStatusChanged;
use Commerce\Iam\Contracts\Activity\IamAuditServiceInterface;

final class LogSystemModuleStatusChanged
{
    public function __construct(private readonly IamAuditServiceInterface $audit) {}

    public function handle(SystemModuleStatusChanged $event): void
    {
        $this->audit->log(
            $event->getEventName(),
            $event->module,
            [
                'code' => $event->module->code,
                'old_status' => $event->oldStatus->value,
                'new_status' => $event->newStatus->value,
            ],
            $event->userId,
        );
    }
}
