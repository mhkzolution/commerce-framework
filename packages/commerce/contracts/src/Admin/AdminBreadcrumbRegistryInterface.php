<?php

declare(strict_types=1);

namespace Commerce\Contracts\Admin;

interface AdminBreadcrumbRegistryInterface
{
    /**
     * @param  list<array{label: string, route?: string|null, url?: string|null}>  $items
     */
    public function register(string $routeName, array $items): void;

    /**
     * @return list<array{label: string, route: ?string, url: ?string, active: bool}>
     */
    public function resolve(?string $routeName = null): array;
}
