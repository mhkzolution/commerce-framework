<?php

declare(strict_types=1);

namespace Commerce\Media;

use Commerce\Contracts\Module\ModuleInterface;

final class MediaModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Media';
    }

    public function getAlias(): string
    {
        return 'media';
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
        return ['iam'];
    }
}
