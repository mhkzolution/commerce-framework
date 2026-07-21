<?php

declare(strict_types=1);

namespace Commerce\Inventory;

use Commerce\Contracts\Module\ModuleInterface;

final class InventoryModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Inventory';
    }

    public function getAlias(): string
    {
        return 'inventory';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getPriority(): int
    {
        return 30;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getSoftDependencies(): array
    {
        return ['iam', 'product'];
    }
}
