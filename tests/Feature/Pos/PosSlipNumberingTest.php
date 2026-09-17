<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use Commerce\Core\Models\Tenant;
use Commerce\Core\Tenant\TenantContext;
use Commerce\Pos\Services\PosSlipNumberGenerator;
use Commerce\Pos\Services\PosSlipSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class PosSlipNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_follow_pos_day_sequence_and_never_use_receipt_prefixes(): void
    {
        Carbon::setTestNow('2026-09-17 10:00:00');

        $service = app(PosSlipSequenceService::class);

        $this->assertSame('POS-20260917-000001', $service->allocate());
        $this->assertSame('POS-20260917-000002', $service->allocate());
        $this->assertSame('POS-20260917-000003', $service->allocate());

        foreach (['POS-20260917-000001', 'POS-20260917-000002', 'POS-20260917-000003'] as $number) {
            $this->assertMatchesRegularExpression('/^POS-\d{8}-\d{6}$/', $number);
            $this->assertStringStartsWith(PosSlipNumberGenerator::PREFIX.'-', $number);
            $this->assertFalse(str_starts_with($number, 'REC-'));
            $this->assertFalse(str_starts_with($number, 'RCP-'));
            $this->assertFalse(str_starts_with($number, 'INV-'));
        }
    }

    public function test_sequence_resets_when_the_day_rolls_over(): void
    {
        $service = app(PosSlipSequenceService::class);

        Carbon::setTestNow('2026-09-17 23:59:00');
        $this->assertSame('POS-20260917-000001', $service->allocate());
        $this->assertSame('POS-20260917-000002', $service->allocate());

        Carbon::setTestNow('2026-09-18 00:00:00');
        $this->assertSame('POS-20260918-000001', $service->allocate());
    }

    public function test_sequences_are_isolated_per_tenant(): void
    {
        config(['commerce.tenant.enabled' => true]);

        $tenantA = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
        ]);
        $tenantB = Tenant::query()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'status' => 'active',
        ]);

        $service = app(PosSlipSequenceService::class);
        $at = Carbon::parse('2026-09-17');

        $this->assertSame('POS-20260917-000001', $service->allocate($at, $tenantA->id));
        $this->assertSame('POS-20260917-000002', $service->allocate($at, $tenantA->id));
        $this->assertSame('POS-20260917-000001', $service->allocate($at, $tenantB->id));
    }

    public function test_tenant_context_scopes_allocation_without_an_explicit_tenant_id(): void
    {
        config(['commerce.tenant.enabled' => true]);

        $tenant = Tenant::query()->create([
            'name' => 'Tenant Context',
            'slug' => 'tenant-context',
            'status' => 'active',
        ]);

        app(TenantContext::class)->set($tenant);
        Carbon::setTestNow('2026-09-17 12:00:00');

        $this->assertSame('POS-20260917-000001', app(PosSlipSequenceService::class)->allocate());
        $this->assertSame('POS-20260917-000002', app(PosSlipSequenceService::class)->allocate());
    }
}
