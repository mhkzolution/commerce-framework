<?php

declare(strict_types=1);

namespace Commerce\Webhooks;

use Commerce\Contracts\Module\ModuleInterface;

final class WebhooksModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Webhooks';
    }

    public function getAlias(): string
    {
        return 'webhooks';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getPriority(): int
    {
        return 31;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getSoftDependencies(): array
    {
        return ['iam', 'orders'];
    }
}
