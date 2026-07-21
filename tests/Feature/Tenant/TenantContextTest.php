<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use Commerce\Core\Models\Tenant;
use Commerce\Core\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_be_created_and_resolved_from_header(): void
    {
        config(['commerce.tenant.enabled' => true]);

        $tenant = Tenant::query()->create([
            'name' => 'Acme Store',
            'slug' => 'acme',
            'status' => 'active',
        ]);

        $this->withHeaders(['X-Tenant' => 'acme'])
            ->getJson(route('api.v1.tenants.current'))
            ->assertOk()
            ->assertJsonPath('data.uuid', $tenant->uuid);

        $context = app(TenantContext::class);
        $this->assertSame($tenant->id, $context->id());
    }

    public function test_tenant_api_lists_tenants(): void
    {
        Tenant::query()->create([
            'name' => 'Beta Shop',
            'slug' => 'beta',
            'status' => 'active',
        ]);

        $this->getJson(route('api.v1.tenants.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
