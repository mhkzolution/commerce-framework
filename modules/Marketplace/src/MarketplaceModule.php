<?php

declare(strict_types=1);

namespace Commerce\Marketplace;

use Commerce\Contracts\Module\ModuleInterface;

final class MarketplaceModule implements ModuleInterface
{
    public function getName(): string { return 'Marketplace'; }
    public function getAlias(): string { return 'marketplace'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getPriority(): int { return 30; }
    public function getDependencies(): array { return []; }
    public function getSoftDependencies(): array { return ['iam']; }
}