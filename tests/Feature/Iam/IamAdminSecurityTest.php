<?php

declare(strict_types=1);

namespace Tests\Feature\Iam;

use Commerce\Iam\Models\IamAuditLog;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IamAdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Commerce\Iam\Database\Seeders\IamSeeder::class);
    }

    public function test_admin_can_view_security_page(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.iam.security.show'))
            ->assertOk()
            ->assertSee('Two-factor authentication')
            ->assertSee('API tokens');
    }

    public function test_admin_can_view_audit_log(): void
    {
        IamAuditLog::query()->create([
            'action' => 'iam.test.action',
            'created_at' => now(),
        ]);

        $this->actingAs(User::query()->first())
            ->get(route('admin.iam.audit-logs.index'))
            ->assertOk()
            ->assertSee('iam.test.action');
    }
}
