<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use Commerce\Currency\Database\Seeders\CurrencySeeder;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Orders\Models\Order;
use Commerce\Payment\Models\Payment;
use Commerce\Pos\Models\Register;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Shipping\Database\Seeders\ShippingMethodSeeder;
use Commerce\Tax\Database\Seeders\TaxRateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class PosOrdersReturnsTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            IamSeeder::class,
            SettingsSeeder::class,
            CurrencySeeder::class,
            ShippingMethodSeeder::class,
            TaxRateSeeder::class,
        ]);
        config([
            'payment.gateway' => 'simulated',
            'payment.simulate_gateway' => true,
        ]);
    }

    public function test_pos_orders_page_lists_pos_channel_orders(): void
    {
        $admin = User::query()->first();
        Register::query()->create([
            'name' => 'POS Orders',
            'code' => 'POS-ORD',
            'is_active' => true,
        ]);

        $variant = $this->createPurchasableProduct(price: 5000, stock: 10, sku: 'POS-ORD-001');

        $this->actingAs($admin)->post(route('pos.session.open'));
        $this->actingAs($admin)->postJson(route('pos.api.cart.items.store'), ['sku' => 'POS-ORD-001']);
        $this->actingAs($admin)->postJson(route('pos.api.checkout'), [
            'payment_method' => 'cash',
            'payments' => [['method' => 'cash', 'amount_minor' => 5000]],
        ])->assertOk();

        $order = Order::query()->where('channel', 'pos')->first();
        $this->assertNotNull($order);

        $this->actingAs($admin)
            ->get(route('pos.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number, false);
    }

    public function test_pos_returns_page_can_refund_paid_order(): void
    {
        $admin = User::query()->first();
        Register::query()->create([
            'name' => 'POS Returns',
            'code' => 'POS-RET',
            'is_active' => true,
        ]);

        $this->createPurchasableProduct(price: 3000, stock: 5, sku: 'POS-RET-001');

        $this->actingAs($admin)->post(route('pos.session.open'));
        $this->actingAs($admin)->postJson(route('pos.api.cart.items.store'), ['sku' => 'POS-RET-001']);
        $this->actingAs($admin)->postJson(route('pos.api.checkout'), [
            'payment_method' => 'cash',
            'payments' => [['method' => 'cash', 'amount_minor' => 3000]],
        ]);

        $order = Order::query()->where('channel', 'pos')->first();
        $payment = Payment::query()->where('order_uuid', $order->uuid)->first();
        $this->assertNotNull($payment);
        $this->assertSame('paid', $payment->status);

        $this->actingAs($admin)
            ->get(route('pos.returns.index', ['search' => $order->order_number]))
            ->assertOk()
            ->assertSee($order->order_number, false)
            ->assertSee('คืนเงินเต็มจำนวน', false);

        $this->actingAs($admin)
            ->post(route('pos.returns.refund'), ['order_uuid' => $order->uuid])
            ->assertRedirect(route('pos.returns.index', ['search' => $order->order_number]))
            ->assertSessionHas('status');

        $this->assertSame('refunded', $payment->fresh()->status);
    }
}
