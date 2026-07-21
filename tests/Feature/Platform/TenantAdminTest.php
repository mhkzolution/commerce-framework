<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use Commerce\Core\Models\Tenant;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Commerce\Iam\Database\Seeders\IamSeeder::class);
    }

    public function test_admin_can_view_tenants_index(): void
    {
        Tenant::query()->create([
            'name' => 'Acme Corp',
            'slug' => 'acme',
            'status' => 'active',
        ]);

        $this->actingAs(User::query()->first())
            ->get(route('admin.platform.tenants.index'))
            ->assertOk()
            ->assertSee('Acme Corp');
    }
}
