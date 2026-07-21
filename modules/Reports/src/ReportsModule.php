<?php

declare(strict_types=1);

namespace Commerce\Reports;

use Commerce\Contracts\Module\ModuleInterface;

final class ReportsModule implements ModuleInterface
{
    public function getName(): string { return 'Reports'; }
    public function getAlias(): string { return 'reports'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getPriority(): int { return 5; }
    public function getDependencies(): array { return []; }
    public function getSoftDependencies(): array { return ['iam', 'orders']; }
}
