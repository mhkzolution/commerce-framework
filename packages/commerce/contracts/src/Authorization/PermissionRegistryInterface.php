<?php

declare(strict_types=1);

namespace Commerce\Contracts\Authorization;

interface PermissionRegistryInterface
{
    /**
     * @param  array{module: string, group?: string, label: string, guard?: string}  $meta
     */
    public function register(string $permission, array $meta): void;

    /**
     * @return list<array{name: string, module: string, group: ?string, label: string}>
     */
    public function all(): array;
}
