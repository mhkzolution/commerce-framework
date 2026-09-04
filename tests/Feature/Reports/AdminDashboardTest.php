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

    public function test_dashboard_shows_old_layout_charts_channel_and_recent_orders(): void
    {
        Carbon::setTestNow('2026-07-23 12:00:00');

        $variant = $this->createPurchasableProduct(price: 2500, stock: 5, sku: 'DASH-001');

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);
        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());
        $this->post(route('storefront.payment.pay', Payment::query()->first()));

        $order = Order::query()->first();
        $this->assertSame('confirmed', $order?->status);

        $series = app(DashboardQueryService::class)->revenueSeries(
            new DashboardDateRange(Carbon::parse('2026-07-17')->startOfDay(), Carbon::parse('2026-07-23')->endOfDay(), '7d'),
        );

        $today = collect($series)->firstWhere('date', '2026-07-23');
        $this->assertNotNull($today);
        $this->assertGreaterThan(0, $today['revenue']);
        $this->assertSame(1, $today['orders']);

        $channels = app(DashboardQueryService::class)->salesByChannel(
            new DashboardDateRange(Carbon::parse('2026-07-17')->startOfDay(), Carbon::parse('2026-07-23')->endOfDay(), '7d'),
        );
        $this->assertNotEmpty($channels);
        $this->assertSame('web', $channels[0]['channel']);

        $this->actingAs(User::query()->first())
            ->get(route('admin.dashboard', ['range' => '7d']))
            ->assertOk()
            ->assertSee(__('reports::admin.daily_revenue'), false)
            ->assertSee(__('reports::admin.daily_orders'), false)
            ->assertSee(__('reports::admin.sales_by_channel'), false)
            ->assertSee(__('reports::admin.recent_orders'), false)
            ->assertSee(__('reports::admin.channel_web'), false)
            ->assertSee(number_format($order->grand_total / 100, 2), false)
            ->assertSee($order->order_number, false);

        Carbon::setTestNow();
    }

    public function test_dashboard_export_downloads_csv(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.dashboard.export', ['range' => '7d']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
