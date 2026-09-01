<?php

declare(strict_types=1);

namespace Commerce\WarehouseScanner;

use Commerce\Contracts\Module\ModuleInterface;

final class WarehouseScannerModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'WarehouseScanner';
    }

    public function getAlias(): string
    {
        return 'warehouse';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getPriority(): int
    {
        return 27;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getSoftDependencies(): array
    {
        return ['iam', 'product', 'inventory', 'barcode', 'orders'];
    }
}
