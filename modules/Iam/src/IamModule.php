<?php

declare(strict_types=1);

namespace Commerce\Iam;

use Commerce\Contracts\Module\ModuleInterface;

final class IamModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'IAM';
    }

    public function getAlias(): string
    {
        return 'iam';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getPriority(): int
    {
        return 10;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getSoftDependencies(): array
    {
        return ['settings'];
    }
}
