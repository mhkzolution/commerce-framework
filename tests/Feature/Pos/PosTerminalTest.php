<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Orders\Models\Order;
use Commerce\Pos\Models\Register;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class PosTerminalTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_cashier_can_complete_pos_sale(): void
    {
        $admin = User::query()->first();
        $register = Register::query()->create([
            'name' => 'Front Counter',
            'code' => 'REG-01',
            'location' => 'Store A',
            'is_active' => true,
        ]);

        $variant = $this->createPurchasableProduct(price: 3500, stock: 10, sku: 'POS-SKU-001');

        $this->actingAs($admin)
            ->get(route('admin.pos.terminal.show', $register))
            ->assertOk()
            ->assertSee('Front Counter');

        $this->actingAs($admin)
            ->post(route('admin.pos.terminal.open', $register), [
                'opening_balance' => 0,
            ])
            ->assertRedirect(route('admin.pos.terminal.show', $register));

        $response = $this->actingAs($admin)
            ->post(route('admin.pos.terminal.items.store', $register), [
                'sku' => 'POS-SKU-001',
                'quantity' => 2,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status');

        $this->actingAs($admin)
            ->post(route('admin.pos.terminal.complete', $register), [
                'customer_name' => 'Walk-in Guest',
            ])
            ->assertRedirect(route('admin.pos.terminal.show', $register))
            ->assertSessionHasNoErrors();

        $order = Order::query()->where('channel', 'pos')->first();
        $this->assertNotNull($order);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame(7000, $order->grand_total);
        $this->assertDatabaseHas('inventory_items', [
            'purchasable_uuid' => $variant->uuid,
            'on_hand' => 8,
        ]);
    }
}
