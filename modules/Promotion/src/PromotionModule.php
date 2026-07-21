<?php

declare(strict_types=1);

namespace Commerce\Promotion;

use Commerce\Contracts\Module\ModuleInterface;

final class PromotionModule implements ModuleInterface
{
    public function getName(): string { return 'Promotion'; }
    public function getAlias(): string { return 'promotion'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getPriority(): int { return 26; }
    public function getDependencies(): array { return []; }
    public function getSoftDependencies(): array { return ['iam']; }
}
