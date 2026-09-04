<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Orders\Models\Order;
use Commerce\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class AdminReportsTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seedCheckoutDependencies();
        config([
            'payment.gateway' => 'simulated',
            'payment.simulate_gateway' => true,
        ]);
    }

    public function test_reports_hub_is_accessible(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('ยอดขายรายวัน', false)
            ->assertSee('รายการคำสั่งซื้อ', false);
    }

    public function test_sales_report_filters_by_channel_and_exports(): void
    {
        $variant = $this->createPurchasableProduct(price: 40, stock: 5, sku: 'RPT-WEB-001');

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);
        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());
        $payment = Payment::query()->first();
        $this->post(route('storefront.payment.pay', $payment));

        $order = Order::query()->first();
        $this->assertSame('web', $order->channel);

        $admin = User::query()->first();

        $this->actingAs($admin)
            ->get(route('admin.reports.sales.index', ['range' => '7d', 'channel' => 'web']))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.reports.sales.export', ['range' => '7d', 'channel' => 'web']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($admin)
            ->get(route('admin.reports.sales.pdf', ['range' => '7d', 'channel' => 'web']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->get(route('admin.reports.sales.print', ['range' => '7d', 'channel' => 'web']))
            ->assertOk()
            ->assertSee('รายงานยอดขายรายวัน', false);
    }

    public function test_orders_report_lists_orders_and_exports_csv(): void
    {
        $variant = $this->createPurchasableProduct(price: 20, stock: 3, sku: 'RPT-ORD-001');

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);
        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());
        $this->post(route('storefront.payment.pay', Payment::query()->first()));

        $order = Order::query()->first();

        $this->actingAs(User::query()->first())
            ->get(route('admin.reports.orders.index', ['range' => '7d']))
            ->assertOk()
            ->assertSee($order->order_number, false);

        $this->actingAs(User::query()->first())
            ->get(route('admin.reports.orders.export', ['range' => '7d']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_products_report_shows_sold_items(): void
    {
        $variant = $this->createPurchasableProduct(price: 35, stock: 4, sku: 'RPT-PROD-001');

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 2,
        ]);
        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());
        $this->post(route('storefront.payment.pay', Payment::query()->first()));

        $this->actingAs(User::query()->first())
            ->get(route('admin.reports.products.index', ['range' => '7d']))
            ->assertOk()
            ->assertSee('RPT-PROD-001', false);
    }
}
