<?php

declare(strict_types=1);

namespace Commerce\Iam\Console;

use Commerce\Contracts\Authorization\PermissionRegistryInterface;
use Commerce\Iam\Models\Permission;
use Commerce\Iam\Models\Role;
use Commerce\Iam\Services\AuthorizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class SyncPermissionsCommand extends Command
{
    protected $signature = 'iam:sync-permissions {--assign-super-admin : Assign all permissions to the super admin role}';

    protected $description = 'Register module permissions from module.json manifests';

    public function handle(
        PermissionRegistryInterface $permissionRegistry,
        AuthorizationService $authorizationService,
    ): int {
        $count = $this->registerModulePermissions($permissionRegistry);
        $count += $this->registerIamPermissions($permissionRegistry);

        $this->info("Synced {$count} permissions.");

        if ($this->option('assign-super-admin')) {
            $role = Role::query()
                ->where('code', config('iam.super_admin_role', 'super-admin'))
                ->first();

            if ($role === null) {
                $this->warn('Super admin role not found. Run iam seeder first.');

                return self::FAILURE;
            }

            $authorizationService->syncRolePermissions(
                $role,
                Permission::query()->pluck('name')->all(),
            );

            $this->info('Assigned all permissions to super admin role.');
        }

        return self::SUCCESS;
    }

    private function registerModulePermissions(PermissionRegistryInterface $permissionRegistry): int
    {
        $count = 0;
        $path = base_path('modules');

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $manifestFile = $path.'/'.$entry.'/module.json';

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
                $count++;
            }
        }

        return $count;
    }

    private function registerIamPermissions(PermissionRegistryInterface $permissionRegistry): int
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
            'iam.audit.view' => 'View audit log',
        ];

        foreach ($permissions as $name => $label) {
            $permissionRegistry->register($name, [
                'module' => 'iam',
                'label' => $label,
            ]);
        }

        return count($permissions);
    }
}
