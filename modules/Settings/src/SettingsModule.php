<?php

declare(strict_types=1);

namespace Commerce\Settings;

use Commerce\Contracts\Module\ModuleInterface;

final class SettingsModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Settings';
    }

    public function getAlias(): string
    {
        return 'settings';
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
        return ['iam'];
    }
}
