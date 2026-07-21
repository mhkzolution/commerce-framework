<?php

declare(strict_types=1);

namespace Commerce\Contracts\Settings;

interface SettingRegistryServiceInterface
{
    /**
     * @param  array{type: string, default?: mixed, label: string, group: string, is_public?: bool}  $schema
     */
    public function register(string $key, array $schema): void;

    /**
     * @return list<array{key: string, schema: array<string, mixed>}>
     */
    public function all(): array;
}
