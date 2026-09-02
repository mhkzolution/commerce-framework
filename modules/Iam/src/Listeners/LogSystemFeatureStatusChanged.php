<?php

declare(strict_types=1);

namespace Commerce\Iam\Listeners;

use Commerce\Core\Events\SystemFeatureStatusChanged;
use Commerce\Iam\Contracts\Activity\IamAuditServiceInterface;

final class LogSystemFeatureStatusChanged
{
    public function __construct(private readonly IamAuditServiceInterface $audit) {}

    public function handle(SystemFeatureStatusChanged $event): void
    {
        $this->audit->log(
            $event->getEventName(),
            $event->feature,
            [
                'code' => $event->feature->code,
                'feature_name' => $event->feature->name,
                'module_code' => $event->feature->module_code,
                'module_name' => $event->moduleName,
                'old_status' => $event->oldStatus->value,
                'new_status' => $event->newStatus->value,
            ],
            $event->userId,
        );
    }
}
