<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Orders\Models\Order;
use Commerce\Pos\Models\Register;
use Commerce\Pos\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class PosSessionLifecycleTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_cashier_must_open_session_before_sales(): void
    {
        $admin = User::query()->first();
        $register = Register::query()->create([
            'name' => 'Counter B',
            'code' => 'REG-02',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pos.terminal.show', $register))
            ->assertOk()
            ->assertSee('Open session');

        $this->actingAs($admin)
            ->post(route('admin.pos.terminal.open', $register), [
                'opening_balance' => 5000,
            ])
            ->assertRedirect(route('admin.pos.terminal.show', $register));

        $session = Session::query()->first();
        $this->assertNotNull($session);
        $this->assertSame('open', $session->status);
        $this->assertSame(5000, $session->opening_balance);
    }

    public function test_sale_links_to_session_and_close_records_variance(): void
    {
        $admin = User::query()->first();
        $register = Register::query()->create([
            'name' => 'Counter C',
            'code' => 'REG-03',
            'is_active' => true,
        ]);

        $this->createPurchasableProduct(price: 2500, stock: 10, sku: 'POS-LIFE-001');

        $this->actingAs($admin)
            ->post(route('admin.pos.terminal.open', $register), ['opening_balance' => 1000]);

        $this->actingAs($admin)
            ->post(route('admin.pos.terminal.items.store', $register), [
                'sku' => 'POS-LIFE-001',
                'quantity' => 1,
            ]);

        $this->actingAs($admin)
            ->post(route('admin.pos.terminal.complete', $register))
            ->assertRedirect();

        $order = Order::query()->where('channel', 'pos')->first();
        $session = Session::query()->first();

        $this->assertNotNull($order);
        $this->assertSame($session->uuid, $order->meta['pos_session_uuid'] ?? null);
        $this->assertSame(2500, $session->fresh()->cash_sales_total);

        $this->actingAs($admin)
            ->post(route('admin.pos.terminal.close', $register), [
                'counted_cash' => 3600,
            ])
            ->assertRedirect(route('admin.pos.registers.index'));

        $session->refresh();
        $this->assertSame('closed', $session->status);
        $this->assertSame(3500, $session->expected_cash);
        $this->assertSame(3600, $session->counted_cash);
        $this->assertSame(100, $session->variance);
    }
}
