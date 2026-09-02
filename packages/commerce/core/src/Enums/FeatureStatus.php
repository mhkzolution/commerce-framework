<?php

declare(strict_types=1);

namespace Commerce\Core\Enums;

enum FeatureStatus: string
{
    case Enabled = 'ENABLED';
    case Disabled = 'DISABLED';

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Enabled => 'success',
            self::Disabled => 'danger',
        };
    }
}
