<?php

declare(strict_types=1);

namespace Commerce\Cms;

use Commerce\Contracts\Module\ModuleInterface;

final class CmsModule implements ModuleInterface
{
    public function getName(): string { return 'Cms'; }
    public function getAlias(): string { return 'cms'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getPriority(): int { return 30; }
    public function getDependencies(): array { return []; }
    public function getSoftDependencies(): array { return ['iam']; }
}