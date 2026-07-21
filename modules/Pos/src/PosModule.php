<?php

declare(strict_types=1);

namespace Commerce\Pos;

use Commerce\Contracts\Module\ModuleInterface;

final class PosModule implements ModuleInterface
{
    public function getName(): string { return 'Pos'; }
    public function getAlias(): string { return 'pos'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getPriority(): int { return 25; }
    public function getDependencies(): array { return []; }
    public function getSoftDependencies(): array { return ['iam']; }
}