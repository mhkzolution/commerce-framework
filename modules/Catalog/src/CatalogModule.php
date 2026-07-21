<?php

declare(strict_types=1);

namespace Commerce\Catalog;

use Commerce\Contracts\Module\ModuleInterface;

final class CatalogModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Catalog';
    }

    public function getAlias(): string
    {
        return 'catalog';
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
        return ['iam', 'media'];
    }
}
