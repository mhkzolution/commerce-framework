<?php

declare(strict_types=1);

namespace Tests\Feature\Iam;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SyncPermissionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_permissions_command_registers_inventory_permissions(): void
    {
        $this->artisan('iam:sync-permissions')
            ->assertSuccessful();

        $this->assertDatabaseHas('permissions', ['name' => 'inventory.purchase_order.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'inventory.purchase_order.manage']);
    }

    public function test_sync_permissions_can_assign_all_permissions_to_super_admin(): void
    {
        $this->seed(IamSeeder::class);

        Permission::query()->where('name', 'inventory.purchase_order.view')->delete();

        $this->artisan('iam:sync-permissions --assign-super-admin')
            ->assertSuccessful();

        $this->assertDatabaseHas('permissions', ['name' => 'inventory.purchase_order.view']);
    }
}
