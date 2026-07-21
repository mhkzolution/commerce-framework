<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Contracts\Authorization\PermissionRegistryInterface;
use Commerce\Iam\Models\Permission;

final class PermissionRegistryService implements PermissionRegistryInterface
{
    public function register(string $permission, array $meta): void
    {
        $parts = explode('.', $permission);
        $module = $meta['module'] ?? ($parts[0] ?? 'system');
        $group = $meta['group'] ?? ($parts[1] ?? null);
        $label = $meta['label'] ?? $permission;
        $guard = $meta['guard'] ?? 'web';

        Permission::query()->updateOrCreate(
            ['name' => $permission],
            [
                'module' => $module,
                'group' => $group,
                'label' => $label,
                'guard_name' => $guard,
            ],
        );
    }

    public function all(): array
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->map(static fn (Permission $permission): array => [
                'name' => $permission->name,
                'module' => $permission->module,
                'group' => $permission->group,
                'label' => $permission->label,
            ])
            ->all();
    }
}
