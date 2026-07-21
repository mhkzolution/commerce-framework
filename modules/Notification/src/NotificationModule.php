<?php

declare(strict_types=1);

namespace Commerce\Notification;

use Commerce\Contracts\Module\ModuleInterface;

final class NotificationModule implements ModuleInterface
{
    public function getName(): string { return 'Notification'; }
    public function getAlias(): string { return 'notification'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getPriority(): int { return 15; }
    public function getDependencies(): array { return []; }
    public function getSoftDependencies(): array { return ['orders']; }
}
