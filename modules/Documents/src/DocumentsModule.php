<?php

declare(strict_types=1);

namespace Commerce\Documents;

use Commerce\Contracts\Module\ModuleInterface;

final class DocumentsModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Documents';
    }

    public function getAlias(): string
    {
        return 'documents';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getPriority(): int
    {
        return 33;
    }

    public function getDependencies(): array
    {
        return ['customers', 'settings'];
    }

    public function getSoftDependencies(): array
    {
        return ['iam', 'orders'];
    }
}
