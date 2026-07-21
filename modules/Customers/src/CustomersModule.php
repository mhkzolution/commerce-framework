<?php

declare(strict_types=1);

namespace Commerce\Customers;

use Commerce\Contracts\Module\ModuleInterface;

final class CustomersModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Customers';
    }

    public function getAlias(): string
    {
        return 'customers';
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
        return ['iam'];
    }
}
