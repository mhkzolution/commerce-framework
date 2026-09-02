<?php

declare(strict_types=1);

namespace Commerce\Core\Enums;

enum ModuleStatus: string
{
    case Active = 'ACTIVE';
    case Hidden = 'HIDDEN';
    case Disabled = 'DISABLED';

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Hidden => 'warning',
            self::Disabled => 'danger',
        };
    }

    public function isAvailable(): bool
    {
        return $this !== self::Disabled;
    }

    public function isVisibleInNavigation(): bool
    {
        return $this === self::Active;
    }
}
