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

    public function test_reports_hub_renders_sales_canvas_not_card_launcher(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee(__('admin::nav.groups.analytics'), false)
            ->assertSee(__('admin::nav.labels.sales_reports'), false)
            ->assertSee(__('admin::nav.labels.order_reports'), false)
            ->assertSee(__('admin::nav.labels.product_reports'), false)
            ->assertDontSee('group block rounded-xl', false)
            ->assertSee('data-analytics-tab="sales"', false)
            ->assertSee('aria-current="page"', false);
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

    public function test_deep_links_mark_the_matching_tab_current(): void
    {
        $admin = User::query()->first();

        $orders = $this->actingAs($admin)
            ->get(route('admin.reports.orders.index'))
            ->assertOk();
        $this->assertActiveTab($orders->getContent(), 'orders');

        $products = $this->actingAs($admin)
            ->get(route('admin.reports.products.index'))
            ->assertOk();
        $this->assertActiveTab($products->getContent(), 'products');

        $sales = $this->actingAs($admin)
            ->get(route('admin.reports.sales.index'))
            ->assertOk();
        $this->assertActiveTab($sales->getContent(), 'sales');

        $hub = $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk();
        $this->assertActiveTab($hub->getContent(), 'sales');
    }

    public function test_tab_links_copy_shared_filter_query(): void
    {
        $html = $this->actingAs(User::query()->first())
            ->get(route('admin.reports.sales.index', ['range' => '7d', 'channel' => 'web']))
            ->assertOk()
            ->getContent();

        $this->assertActiveTab($html, 'sales');
        $this->assertStringContainsString('range=7d', $html);
        $this->assertStringContainsString('channel=web', $html);
        $this->assertMatchesRegularExpression(
            '/data-analytics-tab="orders"[^>]*href="[^"]*range=7d[^"]*channel=web/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/data-analytics-tab="products"[^>]*href="[^"]*range=7d[^"]*channel=web/',
            $html,
        );
    }

    public function test_refresh_preserves_active_tab_and_filters(): void
    {
        $url = route('admin.reports.orders.index', ['range' => '30d', 'channel' => 'web']);
        $admin = User::query()->first();

        $first = $this->actingAs($admin)->get($url)->assertOk()->getContent();
        $second = $this->actingAs($admin)->get($url)->assertOk()->getContent();

        $this->assertActiveTab($first, 'orders');
        $this->assertActiveTab($second, 'orders');
        $this->assertMatchesRegularExpression('/<option[^>]*value="web"[^>]*selected/', $second);
        $this->assertMatchesRegularExpression('/name="range"[^>]*value="custom"|name="range" value="custom"/', $second);
    }

    public function test_sales_and_hub_do_not_run_orders_or_products_queries(): void
    {
        $this->actingAs(User::query()->first());

        $hub = $this->get(route('admin.reports.index'))->assertOk();
        $hubData = $hub->original->getData();
        $this->assertArrayNotHasKey('orders', $hubData);
        $this->assertArrayNotHasKey('byStatus', $hubData);
        $this->assertArrayNotHasKey('products', $hubData);

        $sales = $this->get(route('admin.reports.sales.index'))->assertOk();
        $salesData = $sales->original->getData();
        $this->assertArrayNotHasKey('orders', $salesData);
        $this->assertArrayNotHasKey('byStatus', $salesData);
        $this->assertArrayNotHasKey('products', $salesData);
        $this->assertArrayHasKey('summary', $salesData);
        $this->assertArrayHasKey('dailySeries', $salesData);
        $this->assertArrayHasKey('byChannel', $salesData);
    }

    public function test_orders_tab_does_not_run_sales_series_or_products_queries(): void
    {
        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.reports.orders.index'))
            ->assertOk();

        $data = $response->original->getData();
        $this->assertArrayNotHasKey('dailySeries', $data);
        $this->assertArrayNotHasKey('byChannel', $data);
        $this->assertArrayNotHasKey('products', $data);
        $this->assertArrayHasKey('orders', $data);
        $this->assertArrayHasKey('byStatus', $data);
    }

    public function test_products_tab_does_not_run_orders_or_sales_series_queries(): void
    {
        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.reports.products.index'))
            ->assertOk();

        $data = $response->original->getData();
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('products', $data);
        $this->assertArrayNotHasKey('dailySeries', $data);
        $this->assertArrayNotHasKey('byChannel', $data);
        $this->assertArrayNotHasKey('orders', $data);
    }

    /**
     * @return string the active tab id
     */
    private function assertActiveTab(string $html, string $tab): string
    {
        $this->assertMatchesRegularExpression(
            '/data-analytics-tab="'.$tab.'"[^>]*aria-current="page"/',
            $html,
        );
        foreach (array_diff(['sales', 'orders', 'products'], [$tab]) as $other) {
            $this->assertDoesNotMatchRegularExpression(
                '/data-analytics-tab="'.$other.'"[^>]*aria-current="page"/',
                $html,
            );
        }

        return $tab;
    }
}
