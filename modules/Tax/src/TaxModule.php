<?php

declare(strict_types=1);

namespace Commerce\Tax;

use Commerce\Contracts\Module\ModuleInterface;

final class TaxModule implements ModuleInterface
{
    public function getName(): string { return 'Tax'; }
    public function getAlias(): string { return 'tax'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getPriority(): int { return 25; }
    public function getDependencies(): array { return []; }
    public function getSoftDependencies(): array { return ['iam']; }
}
