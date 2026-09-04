<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Inventory\Models\StockMovement;
use Commerce\Orders\Models\Order;
use Commerce\Orders\Models\OrderEvent;
use Commerce\Orders\Models\OrderShipment;
use Commerce\Payment\Contracts\PaymentServiceInterface;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class AdminOrderDetailFulfillmentTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(IamSeeder::class);
    }

    public function test_detail_page_is_the_operational_workbench(): void
    {
        $admin = User::query()->first();
        $this->assertNotNull($admin);
        $order = $this->createAdminOrder();

        $html = $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number, false)
            ->assertSee('Mina Shore', false)
            ->assertSee('12 Rama IV', false)
            ->assertSee($admin->name, false)
            ->getContent();

        $this->assertStringContainsString('data-order-detail', $html);
        $this->assertStringContainsString('data-financial-status="unpaid"', $html);
        $this->assertStringContainsString('data-fulfillment-status="unfulfilled"', $html);
        $this->assertStringContainsString('data-order-timeline', $html);
        $this->assertStringContainsString('data-inventory-history', $html);
        $this->assertStringContainsString('data-action-panel', $html);
        $this->assertStringContainsString('data-customer-summary', $html);
        $this->assertStringContainsString('name="internal_notes"', $html);
        $this->assertStringContainsString('name="customer_note"', $html);
        $this->assertStringContainsString(__('orders::admin.timeline'), $html);
        $this->assertStringContainsString(__('orders::admin.inventory_history'), $html);
        $this->assertGreaterThan(0, OrderEvent::query()->where('order_id', $order->id)->count());
    }

    public function test_line_items_render_from_snapshots_not_live_catalog(): void
    {
        $variant = $this->createPurchasableProduct(price: 2000, sku: 'SNAP-DETAIL');
        $order = $this->createAdminOrder($variant, [
            'lines' => [
                [
                    'purchasable_uuid' => $variant->uuid,
                    'quantity' => 1,
                    'unit_price' => '15.00',
                ],
            ],
        ]);
        $originalName = (string) $variant->product?->name;

        $variant->product?->update(['name' => 'Changed After Sale']);
        $variant->update(['sku' => 'NEW-SKU', 'price' => 9999]);

        $this->actingAs(User::query()->first())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($originalName)
            ->assertSee('SNAP-DETAIL')
            ->assertDontSee('Changed After Sale')
            ->assertDontSee('NEW-SKU')
            ->assertSee('15.00');
    }

    public function test_inventory_history_lists_reservation_for_admin_create(): void
    {
        $order = $this->createAdminOrder();

        $this->assertTrue(
            StockMovement::query()
                ->where('reference_type', Order::REFERENCE_TYPE)
                ->where('reference_id', $order->uuid)
                ->where('type', 'reservation')
                ->exists(),
        );

        $this->actingAs(User::query()->first())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee(__('orders::admin.movement_reservation'))
            ->assertSee('Admin create');
    }

    public function test_confirm_complete_and_cancel_appear_in_the_timeline(): void
    {
        $admin = User::query()->first();
        $this->assertNotNull($admin);
        $order = $this->createAdminOrder();

        $this->actingAs($admin)
            ->post(route('admin.orders.confirm', $order))
            ->assertRedirect(route('admin.orders.show', $order));

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order->fresh()))
            ->assertOk()
            ->assertSee(__('orders::admin.event_confirmed'))
            ->assertSee(__('orders::admin.movement_sale'));

        $this->actingAs($admin)
            ->post(route('admin.orders.complete', $order))
            ->assertRedirect();

        $html = $this->actingAs($admin)
            ->get(route('admin.orders.show', $order->fresh()))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('orders::admin.event_completed'), $html);
        $this->assertStringContainsString('data-financial-status', $html);
    }

    public function test_internal_and_customer_notes_are_saved_and_audited(): void
    {
        $admin = User::query()->first();
        $this->assertNotNull($admin);
        $order = $this->createAdminOrder();

        $this->actingAs($admin)
            ->post(route('admin.orders.notes.update', $order), [
                'internal_notes' => 'Pack with bubble wrap',
                'customer_note' => 'Leave at the lobby',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame('Pack with bubble wrap', $order->meta['notes'] ?? null);
        $this->assertSame('Leave at the lobby', $order->meta['customer_note'] ?? null);
        $this->assertSame($admin->uuid, $order->updated_by_user_uuid);
        $this->assertTrue(
            OrderEvent::query()
                ->where('order_id', $order->id)
                ->where('type', 'notes.updated')
                ->exists(),
        );

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Pack with bubble wrap')
            ->assertSee('Leave at the lobby')
            ->assertSee(__('orders::admin.event_notes_updated'));
    }

    public function test_shipment_with_tracking_marks_order_fulfilled(): void
    {
        $admin = User::query()->first();
        $this->assertNotNull($admin);
        $order = $this->createAdminOrder();
        $line = $order->lineItems()->first();
        $this->assertNotNull($line);

        $this->actingAs($admin)->post(route('admin.orders.confirm', $order));

        $this->actingAs($admin)
            ->post(route('admin.orders.shipments.store', $order), [
                'carrier' => 'Kerry',
                'tracking_number' => 'KY123456TH',
                'items' => [
                    $line->uuid => ['quantity' => 1],
                ],
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $shipment = OrderShipment::query()->first();
        $this->assertNotNull($shipment);
        $this->assertSame('KY123456TH', $shipment->tracking_number);
        $this->assertSame('Kerry', $shipment->carrier);
        $this->assertSame('shipped', $shipment->status);

        $html = $this->actingAs($admin)
            ->get(route('admin.orders.show', $order->fresh()))
            ->assertOk()
            ->assertSee('KY123456TH')
            ->assertSee('Kerry')
            ->getContent();

        $this->assertStringContainsString('data-fulfillment-status="fulfilled"', $html);
        $this->assertTrue(
            OrderEvent::query()
                ->where('order_id', $order->id)
                ->where('type', 'shipment.created')
                ->exists(),
        );
    }

    public function test_partial_shipment_and_over_fulfillment_are_enforced(): void
    {
        $admin = User::query()->first();
        $variant = $this->createPurchasableProduct(stock: 10);
        $order = $this->createAdminOrder($variant, [
            'lines' => [
                ['purchasable_uuid' => $variant->uuid, 'quantity' => 2],
            ],
        ]);
        $line = $order->lineItems()->first();
        $this->assertNotNull($line);

        $this->actingAs($admin)->post(route('admin.orders.confirm', $order));

        $this->actingAs($admin)
            ->post(route('admin.orders.shipments.store', $order), [
                'tracking_number' => 'PARTIAL-1',
                'items' => [
                    $line->uuid => ['quantity' => 1],
                ],
            ])
            ->assertRedirect();

        $html = $this->actingAs($admin)
            ->get(route('admin.orders.show', $order->fresh()))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-fulfillment-status="partial"', $html);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.shipments.store', $order), [
                'items' => [
                    $line->uuid => ['quantity' => 5],
                ],
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('items');

        $this->assertSame(1, OrderShipment::query()->count());
    }

    public function test_pending_and_cancelled_orders_cannot_be_shipped(): void
    {
        $admin = User::query()->first();
        $order = $this->createAdminOrder();
        $line = $order->lineItems()->first();
        $this->assertNotNull($line);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.shipments.store', $order), [
                'items' => [
                    $line->uuid => ['quantity' => 1],
                ],
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)->post(route('admin.orders.cancel', $order));

        $cancelled = $order->fresh();
        $this->assertNotNull($cancelled);
        $html = $this->actingAs($admin)
            ->get(route('admin.orders.show', $cancelled))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-fulfillment-status="cancelled"', $html);

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $cancelled))
            ->post(route('admin.orders.shipments.store', $cancelled), [
                'items' => [
                    $line->uuid => ['quantity' => 1],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('status');
    }

    public function test_tracking_can_be_updated_and_shipment_cancelled(): void
    {
        $admin = User::query()->first();
        $order = $this->createAdminOrder();
        $line = $order->lineItems()->first();
        $this->assertNotNull($line);
        $this->actingAs($admin)->post(route('admin.orders.confirm', $order));

        $this->actingAs($admin)
            ->post(route('admin.orders.shipments.store', $order), [
                'carrier' => 'Flash',
                'tracking_number' => 'OLD-TRACK',
                'items' => [
                    $line->uuid => ['quantity' => 1],
                ],
            ]);

        $shipment = OrderShipment::query()->first();
        $this->assertNotNull($shipment);

        $this->actingAs($admin)
            ->post(route('admin.orders.shipments.tracking', [$order, $shipment]), [
                'carrier' => 'Flash',
                'tracking_number' => 'NEW-TRACK',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('NEW-TRACK', $shipment->fresh()?->tracking_number);

        $this->actingAs($admin)
            ->post(route('admin.orders.shipments.cancel', [$order, $shipment]))
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('cancelled', $shipment->fresh()?->status);

        $html = $this->actingAs($admin)
            ->get(route('admin.orders.show', $order->fresh()))
            ->assertOk()
            ->assertSee('NEW-TRACK')
            ->getContent();
        $this->assertStringContainsString('data-fulfillment-status="unfulfilled"', $html);
    }

    public function test_paid_payment_drives_financial_status(): void
    {
        $admin = User::query()->first();
        $order = $this->createAdminOrder();

        $payment = app(PaymentServiceInterface::class)->createForOrder(
            $order->uuid,
            $order->grand_total,
            $order->currency,
        );
        app(PaymentServiceInterface::class)->markPaid($payment->uuid, 'MANUAL-1');

        $html = $this->actingAs($admin)
            ->get(route('admin.orders.show', $order->fresh()))
            ->assertOk()
            ->assertSee('MANUAL-1')
            ->getContent();

        $this->assertStringContainsString('data-financial-status="paid"', $html);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAdminOrder(?ProductVariant $variant = null, array $overrides = []): Order
    {
        $variant ??= $this->createPurchasableProduct(stock: 8);

        $this->actingAs(User::query()->first())
            ->post(route('admin.orders.store'), $this->payload($variant, $overrides))
            ->assertRedirect();

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order);

        return $order->load('lineItems');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(ProductVariant $variant, array $overrides = []): array
    {
        return array_replace_recursive([
            'intent' => 'create',
            'idempotency_key' => (string) Str::uuid(),
            'customer_name' => 'Mina Shore',
            'customer_email' => 'mina@example.com',
            'customer_phone' => '0890001111',
            'billing_same_as_shipping' => '1',
            'notes' => 'Call before delivery',
            'shipping_address' => [
                'recipient_name' => 'Mina Shore',
                'phone' => '0890001111',
                'line1' => '12 Rama IV',
                'district' => 'Bang Rak',
                'province' => 'Bangkok',
                'postal_code' => '10500',
            ],
            'lines' => [
                ['purchasable_uuid' => $variant->uuid, 'quantity' => 1],
            ],
        ], $overrides);
    }
}
