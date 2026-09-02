<?php

declare(strict_types=1);

namespace App\Support\Admin;

final class AdminUi
{
    public static function navIsActive(?string $routeName): bool
    {
        try {
            if ($routeName === null || $routeName === '') {
                return false;
            }

            $current = request()->route()?->getName();

            if ($current === null || $current === '') {
                return false;
            }

            if ($current === $routeName) {
                return true;
            }

            return str_starts_with($current, $routeName.'.');
        } catch (\Throwable) {
            return false;
        }
    }

    public static function groupIsOpen(array $item): bool
    {
        try {
            if (($item['default_open'] ?? false) === true) {
                return true;
            }

            foreach ($item['children'] ?? [] as $child) {
                if (! is_array($child)) {
                    continue;
                }

                if (($child['type'] ?? 'link') === 'group') {
                    if (self::groupIsOpen($child)) {
                        return true;
                    }
                } elseif (self::navIsActive($child['route'] ?? null)) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable) {
            return false;
        }
    }
}
