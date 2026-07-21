<?php

declare(strict_types=1);

namespace Commerce\Iam\Database\Seeders;

use Commerce\Contracts\Authorization\PermissionRegistryInterface;
use Commerce\Iam\Contracts\User\UserServiceInterface;
use Commerce\Iam\DTO\CreateUserData;
use Commerce\Iam\Models\Permission;
use Commerce\Iam\Models\Role;
use Commerce\Iam\Models\User;
use Commerce\Iam\Services\AuthorizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class IamSeeder extends Seeder
{
    public function run(
        PermissionRegistryInterface $permissionRegistry,
        UserServiceInterface $userService,
        AuthorizationService $authorizationService,
    ): void {
        $this->registerModulePermissions($permissionRegistry);
        $this->seedIamPermissions($permissionRegistry);

        $role = Role::query()->updateOrCreate(
            ['code' => config('iam.super_admin_role', 'super-admin'), 'tenant_id' => null],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Super Admin',
                'description' => 'Full system access',
                'is_system' => true,
            ],
        );

        $authorizationService->syncRolePermissions(
            $role,
            Permission::query()->pluck('name')->all(),
        );

        $admin = config('iam.default_admin');
        $superAdminCode = config('iam.super_admin_role', 'super-admin');

        $user = User::query()
            ->whereHas('roles', static fn ($query) => $query->where('code', $superAdminCode))
            ->first();

        if ($user === null) {
            $user = $userService->findByEmail($admin['email']);
        }

        if ($user === null) {
            $userService->create(new CreateUserData(
                name: $admin['name'],
                email: $admin['email'],
                password: $admin['password'],
                roleCodes: [$superAdminCode],
            ));

            return;
        }

        $user->forceFill([
            'name' => $admin['name'],
            'email' => $admin['email'],
            'password' => Hash::make($admin['password']),
            'status' => 'active',
        ])->save();

        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    private function registerModulePermissions(PermissionRegistryInterface $permissionRegistry): void
    {
        $path = base_path('modules');

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $manifestFile = $path . '/' . $entry . '/module.json';

            if (! is_file($manifestFile)) {
                continue;
            }

            $manifest = json_decode(file_get_contents($manifestFile), true, 512, JSON_THROW_ON_ERROR);
            $module = $manifest['alias'] ?? strtolower($entry);

            foreach ($manifest['permissions'] ?? [] as $permission) {
                $permissionRegistry->register($permission, [
                    'module' => $module,
                    'label' => Str::headline(str_replace('.', ' ', $permission)),
                ]);
            }
        }
    }

    private function seedIamPermissions(PermissionRegistryInterface $permissionRegistry): void
    {
        $permissions = [
            'iam.user.view' => 'View users',
            'iam.user.create' => 'Create users',
            'iam.user.update' => 'Update users',
            'iam.user.delete' => 'Delete users',
            'iam.role.view' => 'View roles',
            'iam.role.create' => 'Create roles',
            'iam.role.update' => 'Update roles',
            'iam.role.delete' => 'Delete roles',
            'iam.permission.view' => 'View permissions',
        ];

        foreach ($permissions as $name => $label) {
            $permissionRegistry->register($name, [
                'module' => 'iam',
                'label' => $label,
            ]);
        }
    }
}
