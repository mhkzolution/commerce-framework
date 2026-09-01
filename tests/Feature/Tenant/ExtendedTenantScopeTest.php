<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use Commerce\Core\Models\Tenant;
use Commerce\Core\Tenant\TenantContext;
use Commerce\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExtendedTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_payments_are_scoped_to_current_tenant(): void
    {
        config(['commerce.tenant.enabled' => true]);

        $tenantA = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a-pay',
            'status' => 'active',
        ]);

        $tenantB = Tenant::query()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b-pay',
            'status' => 'active',
        ]);

        $context = app(TenantContext::class);

        $context->set($tenantA);
        Payment::query()->create([
            'order_uuid' => '00000000-0000-4000-8000-0000000000a1',
            'amount' => 1000,
            'currency' => 'USD',
            'status' => 'pending',
            'method' => 'simulated',
        ]);

        $context->set($tenantB);
        Payment::query()->create([
            'order_uuid' => '00000000-0000-4000-8000-0000000000b1',
            'amount' => 2000,
            'currency' => 'USD',
            'status' => 'pending',
            'method' => 'simulated',
        ]);

        $context->set($tenantA);
        $this->assertSame(1, Payment::query()->count());
        $this->assertSame(1000, Payment::query()->first()?->amount);

        $context->set($tenantB);
        $this->assertSame(1, Payment::query()->count());
        $this->assertSame(2000, Payment::query()->first()?->amount);
    }
}
