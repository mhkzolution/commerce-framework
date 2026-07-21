<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Iam\Contracts\Role\RoleServiceInterface;
use Commerce\Iam\DTO\CreateRoleData;
use Commerce\Iam\DTO\UpdateRoleData;
use Commerce\Iam\Models\Role;
use Illuminate\Support\Str;
use RuntimeException;

final class RoleService extends BaseService implements RoleServiceInterface
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
    ) {}

    public function create(CreateRoleData $data): Role
    {
        $role = Role::query()->create([
            'name' => $data->name,
            'code' => $data->code,
            'description' => $data->description,
            'is_system' => false,
        ]);

        if ($data->permissionNames !== []) {
            $this->authorizationService->syncRolePermissions($role, $data->permissionNames);
        }

        return $role->fresh(['permissions']);
    }

    public function update(string $uuid, UpdateRoleData $data): Role
    {
        $role = $this->findByUuid($uuid);

        if ($role === null) {
            throw new RuntimeException("Role [{$uuid}] not found.");
        }

        $role->update([
            'name' => $data->name,
            'description' => $data->description,
        ]);

        $this->authorizationService->syncRolePermissions($role, $data->permissionNames);

        return $role->fresh(['permissions']);
    }

    public function delete(string $uuid): void
    {
        $role = $this->findByUuid($uuid);

        if ($role === null) {
            throw new RuntimeException("Role [{$uuid}] not found.");
        }

        if ($role->is_system) {
            throw new RuntimeException('System roles cannot be deleted.');
        }

        if ($role->code === config('iam.super_admin_role', 'super-admin')) {
            throw new RuntimeException('The super admin role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            throw new RuntimeException('Cannot delete a role that is assigned to users.');
        }

        $role->permissions()->detach();
        $role->delete();
    }

    public function findByUuid(string $uuid): ?Role
    {
        return Role::query()->where('uuid', $uuid)->first();
    }

    public static function generateCode(string $name): string
    {
        return Str::slug($name);
    }
}
