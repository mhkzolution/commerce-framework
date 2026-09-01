<?php

declare(strict_types=1);

namespace Tests\Feature\Marketplace;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Marketplace\Models\Commission;
use Commerce\Marketplace\Models\Payout;
use Commerce\Marketplace\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MarketplacePayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_seller_can_access_portal_and_request_payout(): void
    {
        $user = User::query()->first();
        $this->assertNotNull($user);

        $seller = Seller::query()->create([
            'name' => 'Portal Vendor',
            'slug' => 'portal-vendor',
            'email' => 'portal@example.com',
            'commission_rate' => 1000,
            'status' => 'active',
            'user_uuid' => $user->uuid,
        ]);

        Commission::query()->create([
            'order_uuid' => (string) str()->uuid(),
            'order_line_item_uuid' => (string) str()->uuid(),
            'seller_uuid' => $seller->uuid,
            'line_total' => 10000,
            'commission_rate' => 1000,
            'commission_amount' => 1000,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('seller.dashboard'))
            ->assertOk()
            ->assertSee('Portal Vendor')
            ->assertSee('10.00');

        $this->actingAs($user)
            ->post(route('seller.payouts.request'))
            ->assertRedirect(route('seller.dashboard'));

        $payout = Payout::query()->first();
        $this->assertNotNull($payout);
        $this->assertSame(1000, $payout->amount);
        $this->assertSame('pending', $payout->status);
    }

    public function test_admin_can_mark_payout_as_paid(): void
    {
        $seller = Seller::query()->create([
            'name' => 'Paid Vendor',
            'slug' => 'paid-vendor',
            'commission_rate' => 500,
            'status' => 'active',
        ]);

        $payout = Payout::query()->create([
            'seller_uuid' => $seller->uuid,
            'amount' => 2500,
            'status' => 'pending',
        ]);

        Commission::query()->create([
            'order_uuid' => (string) str()->uuid(),
            'order_line_item_uuid' => (string) str()->uuid(),
            'seller_uuid' => $seller->uuid,
            'line_total' => 50000,
            'commission_rate' => 500,
            'commission_amount' => 2500,
            'status' => 'pending',
            'payout_uuid' => $payout->uuid,
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.marketplace.payouts.mark-paid', $payout), [
                'reference' => 'WIRE-123',
            ])
            ->assertRedirect(route('admin.marketplace.payouts.index'));

        $payout->refresh();
        $this->assertSame('paid', $payout->status);
        $this->assertSame('WIRE-123', $payout->reference);
        $this->assertDatabaseHas('marketplace_commissions', [
            'payout_uuid' => $payout->uuid,
            'status' => 'paid',
        ]);
    }
}
