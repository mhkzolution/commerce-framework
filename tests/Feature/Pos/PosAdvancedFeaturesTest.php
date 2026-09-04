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

final class PosAdvancedFeaturesTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_pos_supports_price_override_and_mixed_payment_checkout(): void
    {
        $admin = User::query()->first();
        Register::query()->create([
            'name' => 'Advanced Counter',
            'code' => 'POS-ADV',
            'is_active' => true,
        ]);

        $variant = $this->createPurchasableProduct(price: 100, stock: 10, sku: 'POS-ADV-001');

        $this->actingAs($admin)->post(route('pos.session.open'));

        $this->actingAs($admin)
            ->postJson(route('pos.api.cart.items.store'), ['sku' => 'POS-ADV-001', 'quantity' => 1])
            ->assertOk();

        $this->actingAs($admin)
            ->patchJson(route('pos.api.cart.items.price', ['purchasable' => $variant->uuid]), [
                'unit_price_minor' => 7500,
            ])
            ->assertOk()
            ->assertJsonPath('cart.lines.0.unit_price_minor', 7500);

        $this->actingAs($admin)
            ->patchJson(route('pos.api.payments'), [
                'payments' => [
                    ['method' => 'cash', 'amount_minor' => 4000],
                    ['method' => 'card', 'amount_minor' => 3500],
                ],
            ])
            ->assertOk();

        $response = $this->actingAs($admin)
            ->postJson(route('pos.api.checkout'), [
                'payments' => [
                    ['method' => 'cash', 'amount_minor' => 4000],
                    ['method' => 'card', 'amount_minor' => 3500],
                ],
                'payment_method' => 'mixed',
            ]);

        $response->assertOk()->assertJsonStructure(['receipt' => ['order_number', 'print_url', 'payments']]);

        $order = Order::query()->where('channel', 'pos')->latest()->first();
        $this->assertNotNull($order);
        $this->assertSame(7500, $order->subtotal);
        $this->assertIsArray($order->meta['pos_payments'] ?? null);
        $this->assertCount(2, $order->meta['pos_payments']);
    }

    public function test_pos_receipt_page_is_printable(): void
    {
        $admin = User::query()->first();
        Register::query()->create(['name' => 'Receipt', 'code' => 'POS-RCP', 'is_active' => true]);
        $this->createPurchasableProduct(price: 1000, stock: 5, sku: 'POS-RCP-001');

        $this->actingAs($admin)->post(route('pos.session.open'));
        $this->actingAs($admin)->postJson(route('pos.api.cart.items.store'), ['sku' => 'POS-RCP-001']);
        $checkout = $this->actingAs($admin)->postJson(route('pos.api.checkout'), [
            'payment_method' => 'cash',
            'payments' => [['method' => 'cash', 'amount_minor' => 1000]],
        ]);

        $orderUuid = $checkout->json('receipt.order_uuid');
        $this->assertNotNull($orderUuid);

        $this->actingAs($admin)
            ->get(route('pos.receipt.show', ['orderUuid' => $orderUuid]))
            ->assertOk()
            ->assertSee('RECEIPT');
    }

    public function test_pos_sync_endpoint_replays_queued_actions(): void
    {
        $admin = User::query()->first();
        Register::query()->create(['name' => 'Sync', 'code' => 'POS-SYNC', 'is_active' => true]);
        $this->createPurchasableProduct(price: 20, stock: 5, sku: 'POS-SYNC-001');

        $this->actingAs($admin)->post(route('pos.session.open'));

        $response = $this->actingAs($admin)->postJson(route('pos.api.sync'), [
            'actions' => [
                [
                    'id' => 'action-1',
                    'type' => 'add_item',
                    'payload' => ['sku' => 'POS-SYNC-001', 'quantity' => 2],
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('sync_results.0.status', 'ok')
            ->assertJsonPath('cart.item_count', 2);
    }
}
