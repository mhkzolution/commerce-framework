<?php

declare(strict_types=1);

namespace Commerce\Currency;

use Commerce\Contracts\Module\ModuleInterface;

final class CurrencyModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Currency';
    }

    public function getAlias(): string
    {
        return 'currency';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getPriority(): int
    {
        return 15;
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
