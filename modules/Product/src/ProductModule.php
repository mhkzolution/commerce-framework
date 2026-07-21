<?php

declare(strict_types=1);

namespace Commerce\Product;

use Commerce\Contracts\Module\ModuleInterface;

final class ProductModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Product';
    }

    public function getAlias(): string
    {
        return 'product';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getPriority(): int
    {
        return 20;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getSoftDependencies(): array
    {
        return ['iam', 'catalog', 'media', 'settings'];
    }
}
