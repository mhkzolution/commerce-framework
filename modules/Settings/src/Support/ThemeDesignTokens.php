<?php

declare(strict_types=1);

namespace Commerce\Settings\Support;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;

final class ThemeDesignTokens
{
    /**
     * @return array<string, string>
     */
    public static function resolve(): array
    {
        $overrides = config('design.overrides', []);

        if (! app()->bound(SettingQueryServiceInterface::class)) {
            return $overrides;
        }

        $theme = app(SettingQueryServiceInterface::class)->getGroup('theme');

        $map = [
            'primary' => 'primary',
            'primary_hover' => 'primary-hover',
            'primary_active' => 'primary-active',
            'background' => 'background',
            'surface' => 'surface',
            'accent' => 'accent',
            'accent_hover' => 'accent-hover',
        ];

        foreach ($map as $settingKey => $token) {
            $value = $theme[$settingKey] ?? null;

            if (! is_string($value) || $value === '') {
                continue;
            }

            $overrides[$token] = $value;
        }

        return $overrides;
    }
}
