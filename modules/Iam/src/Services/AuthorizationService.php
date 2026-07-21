<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\Iam\Models\Permission;
use Commerce\Iam\Models\Role;
use Commerce\Iam\Models\User;
use Illuminate\Support\Facades\Cache;

final class AuthorizationService implements AuthorizationServiceInterface
{
    public function can(?object $user, string $permission, mixed $resource = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($this->hasRole($user, (string) config('iam.super_admin_role', 'super-admin'))) {
            return true;
        }

        return in_array($permission, $this->getPermissionsForUser($user->id), true);
    }

    public function hasRole(?object $user, string $roleCode): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $user->roles()->where('code', $roleCode)->exists();
    }

    public function getPermissionsForUser(int $userId): array
    {
        return Cache::remember(
            "iam.permissions.user.{$userId}",
            now()->addHour(),
            function () use ($userId): array {
                $roleIds = User::query()->find($userId)?->roles()->pluck('roles.id') ?? collect();

                if ($roleIds->isEmpty()) {
                    return [];
                }

                return Permission::query()
                    ->whereHas('roles', static fn ($query) => $query->whereIn('roles.id', $roleIds))
                    ->pluck('name')
                    ->unique()
                    ->values()
                    ->all();
            },
        );
    }

    public function clearCacheForUser(int $userId): void
    {
        Cache::forget("iam.permissions.user.{$userId}");
    }

    public function syncRolePermissions(Role $role, array $permissionNames): void
    {
        $permissionIds = Permission::query()
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        $role->permissions()->sync($permissionIds);

        foreach ($role->users as $user) {
            $this->clearCacheForUser($user->id);
        }
    }
}
