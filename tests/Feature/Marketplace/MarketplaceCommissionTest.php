<?php

declare(strict_types=1);

namespace Tests\Feature\Marketplace;

use Commerce\Iam\Models\User;
use Commerce\Marketplace\Models\Commission;
use Commerce\Marketplace\Models\Seller;
use Commerce\Orders\Models\Order;
use Commerce\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class MarketplaceCommissionTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCheckoutDependencies();
    }

    public function test_commission_is_recorded_when_order_is_confirmed(): void
    {
        $seller = Seller::query()->create([
            'name' => 'Acme Vendor',
            'slug' => 'acme-vendor',
            'email' => 'vendor@example.com',
            'commission_rate' => 1000,
            'status' => 'active',
        ]);

        $variant = $this->createPurchasableProduct(price: 10000, stock: 5, sku: 'VENDOR-SKU');
        $variant->product->update(['seller_uuid' => $seller->uuid]);

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);

        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());

        $order = Order::query()->first();
        $payment = Payment::query()->where('order_uuid', $order->uuid)->first();

        $this->post(route('storefront.payment.pay', $payment));

        $this->assertDatabaseHas('marketplace_commissions', [
            'order_uuid' => $order->uuid,
            'seller_uuid' => $seller->uuid,
            'line_total' => 10000,
            'commission_amount' => 1000,
        ]);

        $commission = Commission::query()->first();
        $this->assertNotNull($commission);
    }

    public function test_admin_can_view_commissions_index(): void
    {
        $this->seed(\Commerce\Iam\Database\Seeders\IamSeeder::class);

        $seller = Seller::query()->create([
            'name' => 'Acme Vendor',
            'slug' => 'acme-vendor',
            'email' => 'vendor@example.com',
            'commission_rate' => 1000,
            'status' => 'active',
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

        $this->actingAs(User::query()->first())
            ->get(route('admin.marketplace.commissions.index'))
            ->assertOk()
            ->assertSee('Acme Vendor')
            ->assertSee('10.00%');
    }
}
