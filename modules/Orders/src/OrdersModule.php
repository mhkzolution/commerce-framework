<?php

declare(strict_types=1);

namespace Commerce\Orders;

use Commerce\Contracts\Module\ModuleInterface;

final class OrdersModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Orders';
    }

    public function getAlias(): string
    {
        return 'orders';
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
        return ['iam', 'product', 'inventory'];
    }
}
