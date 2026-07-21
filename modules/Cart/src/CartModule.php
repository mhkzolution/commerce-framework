<?php

declare(strict_types=1);

namespace Commerce\Cart;

use Commerce\Contracts\Module\ModuleInterface;

final class CartModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Cart';
    }

    public function getAlias(): string
    {
        return 'cart';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getPriority(): int
    {
        return 28;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getSoftDependencies(): array
    {
        return ['product', 'inventory', 'orders', 'customers'];
    }
}
