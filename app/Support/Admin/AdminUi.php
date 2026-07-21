<?php

declare(strict_types=1);

namespace App\Support\Admin;

final class AdminUi
{
    public static function navIsActive(?string $routeName): bool
    {
        if ($routeName === null) {
            return false;
        }

        $current = request()->route()?->getName();

        if ($current === null) {
            return false;
        }

        if ($current === $routeName) {
            return true;
        }

        return str_starts_with($current, $routeName . '.');
    }

    public static function groupIsOpen(array $item): bool
    {
        if (($item['default_open'] ?? false) === true) {
            return true;
        }

        foreach ($item['children'] ?? [] as $child) {
            if (($child['type'] ?? 'link') === 'group') {
                if (self::groupIsOpen($child)) {
                    return true;
                }
            } elseif (self::navIsActive($child['route'] ?? null)) {
                return true;
            }
        }

        return false;
    }
}
