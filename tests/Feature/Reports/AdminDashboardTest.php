<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Orders\Models\Order;
use Commerce\Payment\Models\Payment;
use Commerce\Reports\Services\DashboardQueryService;
use Commerce\Reports\Support\DashboardDateRange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class AdminDashboardTest extends TestCase
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

    public function test_dashboard_shows_revenue_and_chart_data_for_paid_orders(): void
    {
        Carbon::setTestNow('2026-07-23 12:00:00');

        $variant = $this->createPurchasableProduct(price: 40, stock: 5, sku: 'DASH-001');

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);
        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());
        $this->post(route('storefront.payment.pay', Payment::query()->first()));

        $order = Order::query()->first();
        $this->assertSame('confirmed', $order->status);

        $series = app(DashboardQueryService::class)->revenueSeries(
            new DashboardDateRange(Carbon::parse('2026-07-17')->startOfDay(), Carbon::parse('2026-07-23')->endOfDay(), '7d'),
        );

        $today = collect($series)->firstWhere('date', '2026-07-23');
        $this->assertNotNull($today);
        $this->assertGreaterThan(0, $today['revenue']);
        $this->assertSame(1, $today['orders']);

        $this->actingAs(User::query()->first())
            ->get(route('admin.dashboard', ['range' => '7d']))
            ->assertOk()
            ->assertSee('ยอดขายรายวัน', false)
            ->assertSee('จำนวนออเดอร์รายวัน', false)
            ->assertSee(number_format($order->grand_total / 100, 2), false)
            ->assertSee($order->order_number, false);

        Carbon::setTestNow();
    }
}
