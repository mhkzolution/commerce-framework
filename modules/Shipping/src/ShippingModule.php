<?php

declare(strict_types=1);

namespace Commerce\Shipping;

use Commerce\Contracts\Module\ModuleInterface;

final class ShippingModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Shipping';
    }

    public function getAlias(): string
    {
        return 'shipping';
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
        return ['iam'];
    }
}
