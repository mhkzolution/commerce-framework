<?php

declare(strict_types=1);

namespace Commerce\Contracts\Settings;

interface SettingQueryServiceInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;

    /**
     * @return array<string, mixed>
     */
    public function getGroup(string $group): array;
}
