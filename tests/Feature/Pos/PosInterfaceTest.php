<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use Commerce\Currency\Database\Seeders\CurrencySeeder;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Orders\Models\Order;
use Commerce\Pos\Models\Register;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class PosInterfaceTest extends TestCase
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
        ]);
    }

    public function test_pos_interface_supports_full_sale_flow(): void
    {
        $admin = User::query()->first();
        $register = Register::query()->create([
            'name' => 'POS Counter',
            'code' => 'POS-01',
            'location' => 'Store A',
            'is_active' => true,
        ]);

        $variant = $this->createPurchasableProduct(price: 5000, stock: 10, sku: 'POS-IFACE-001');

        $this->actingAs($admin)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('เปิดกะก่อนเริ่มขายหน้าร้าน');

        $this->actingAs($admin)
            ->post(route('pos.session.open'), ['opening_balance' => 0])
            ->assertRedirect(route('pos.index'));

        $this->actingAs($admin)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('pos-app');

        $this->actingAs($admin)
            ->postJson(route('pos.api.cart.items.store'), [
                'sku' => 'POS-IFACE-001',
                'quantity' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('cart.item_count', 2)
            ->assertJsonPath('cart.currency', 'THB')
            ->assertJsonPath('totals.currency', 'THB');

        $this->actingAs($admin)
            ->postJson(route('pos.api.checkout'), [
                'payment_method' => 'cash',
                'amount_received' => 10000,
            ])
            ->assertOk()
            ->assertJsonPath('cart.item_count', 0)
            ->assertJsonStructure(['receipt' => ['order_number', 'grand_total']]);

        $order = Order::query()->where('channel', 'pos')->latest()->first();
        $this->assertNotNull($order);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame(10000, $order->grand_total);
        $this->assertDatabaseHas('inventory_items', [
            'purchasable_uuid' => $variant->uuid,
            'on_hand' => 8,
        ]);
    }

    public function test_pos_can_hold_and_resume_sale(): void
    {
        $admin = User::query()->first();
        Register::query()->create([
            'name' => 'Hold Counter',
            'code' => 'POS-HOLD',
            'is_active' => true,
        ]);

        $this->createPurchasableProduct(price: 25, stock: 5, sku: 'POS-HOLD-001');

        $this->actingAs($admin)->post(route('pos.session.open'));
        $this->actingAs($admin)->postJson(route('pos.api.cart.items.store'), ['sku' => 'POS-HOLD-001']);

        $holdResponse = $this->actingAs($admin)->postJson(route('pos.api.hold'));
        $holdResponse->assertOk()->assertJsonPath('cart.item_count', 0);

        $holdId = $holdResponse->json('holds.0.id');
        $this->assertNotNull($holdId);

        $this->actingAs($admin)
            ->postJson(route('pos.api.holds.resume', ['holdId' => $holdId]))
            ->assertOk()
            ->assertJsonPath('cart.item_count', 1);
    }
}
