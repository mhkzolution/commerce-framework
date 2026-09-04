<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Orders\Models\Order;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class AdminOrderCreateReadinessTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(IamSeeder::class);
    }

    public function test_create_page_exposes_idempotency_billing_and_unit_price_controls(): void
    {
        $html = $this->actingAs(User::query()->first())
            ->get(route('admin.orders.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="idempotency_key"', $html);
        $this->assertStringContainsString('name="billing_same_as_shipping"', $html);
        $this->assertStringContainsString('name="billing_address[line1]"', $html);
        $this->assertStringContainsString('data-unit-price', $html);
    }

    public function test_line_snapshots_survive_catalog_changes_and_soft_deletes(): void
    {
        $variant = $this->createPurchasableProduct(price: 2000, stock: 8, sku: 'SNAP-SKU');
        $product = $variant->product;
        $this->assertNotNull($product);
        $originalName = (string) $product->name;

        $this->actingAs(User::query()->first())
            ->post(route('admin.orders.store'), $this->payload($variant, [
                'lines' => [
                    ['purchasable_uuid' => $variant->uuid, 'quantity' => 1],
                ],
            ]))
            ->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $line = $order->lineItems()->first();
        $this->assertNotNull($line);
        $this->assertSame($originalName, $line->name);
        $this->assertSame('SNAP-SKU', $line->sku);
        $this->assertSame(2000, $line->unit_price);

        $product->update(['name' => 'Renamed After Sale']);
        $variant->update(['sku' => 'CHANGED-SKU', 'price' => 9999, 'name' => 'Renamed Variant']);

        $order->refresh();
        $line = $order->lineItems()->first();
        $this->assertSame($originalName, $line->name);
        $this->assertSame('SNAP-SKU', $line->sku);
        $this->assertSame(2000, $line->unit_price);

        $variant->delete();
        $product->delete();

        $this->actingAs(User::query()->first())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($originalName)
            ->assertSee('SNAP-SKU');
    }

    public function test_soft_deleted_variant_cannot_be_added_to_a_new_order(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'GONE-SKU');
        $variant->delete();

        $this->actingAs(User::query()->first())
            ->from(route('admin.orders.create'))
            ->post(route('admin.orders.store'), $this->payload($variant, [
                'lines' => [
                    ['purchasable_uuid' => $variant->uuid, 'quantity' => 1],
                ],
            ]))
            ->assertRedirect(route('admin.orders.create'))
            ->assertSessionHasErrors('lines');

        $this->assertSame(0, Order::query()->count());
        $this->assertTrue(ProductVariant::withTrashed()->where('uuid', $variant->uuid)->exists());
    }

    public function test_shipping_and_billing_addresses_are_snapshotted(): void
    {
        $variant = $this->createPurchasableProduct();

        $this->actingAs(User::query()->first())
            ->post(route('admin.orders.store'), $this->payload($variant, [
                'billing_same_as_shipping' => '0',
                'shipping_address' => [
                    'recipient_name' => 'Ship To',
                    'phone' => '0811111111',
                    'line1' => '12 Rama IV',
                    'district' => 'Bang Rak',
                    'province' => 'Bangkok',
                    'postal_code' => '10500',
                ],
                'billing_address' => [
                    'recipient_name' => 'Bill To',
                    'phone' => '0822222222',
                    'line1' => '88 Silom',
                    'district' => 'Bang Rak',
                    'province' => 'Bangkok',
                    'postal_code' => '10500',
                ],
            ]))
            ->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame('12 Rama IV', $order->shipping_address['line1'] ?? null);
        $this->assertSame('Ship To', $order->shipping_address['recipient_name'] ?? null);
        $this->assertSame('88 Silom', $order->billing_address['line1'] ?? null);
        $this->assertSame('Bill To', $order->billing_address['recipient_name'] ?? null);
    }

    public function test_created_by_is_the_authenticated_admin(): void
    {
        $admin = User::query()->first();
        $variant = $this->createPurchasableProduct();

        $this->actingAs($admin)
            ->post(route('admin.orders.store'), $this->payload($variant))
            ->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame($admin->uuid, $order->created_by_user_uuid);
        $this->assertSame($admin->uuid, $order->updated_by_user_uuid);
    }

    public function test_duplicate_idempotency_key_does_not_create_a_second_order(): void
    {
        $variant = $this->createPurchasableProduct(stock: 10);
        $key = (string) Str::uuid();
        $payload = $this->payload($variant, ['idempotency_key' => $key]);
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->post(route('admin.orders.store'), $payload)
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.orders.store'), $payload)
            ->assertRedirect();

        $this->assertSame(1, Order::query()->count());
    }

    public function test_line_item_price_override_is_snapshotted_into_totals(): void
    {
        $variant = $this->createPurchasableProduct(price: 2000, stock: 5, sku: 'OVR-SKU');

        $this->actingAs(User::query()->first())
            ->post(route('admin.orders.store'), $this->payload($variant, [
                'lines' => [
                    [
                        'purchasable_uuid' => $variant->uuid,
                        'quantity' => 2,
                        'unit_price' => '15.00',
                    ],
                ],
            ]))
            ->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $line = $order->lineItems()->first();
        $this->assertNotNull($line);
        $this->assertSame(1500, $line->unit_price);
        $this->assertSame(3000, $line->line_total);
        $this->assertSame(3000, $order->subtotal);
        $this->assertTrue((bool) ($line->meta['price_overridden'] ?? false));
        $this->assertSame(2000, $line->meta['catalog_unit_price'] ?? null);
        $this->assertSame(2000, $variant->fresh()->price);
    }

    public function test_non_draft_admin_order_reserves_inventory_when_policy_enabled(): void
    {
        $variant = $this->createPurchasableProduct(stock: 6, sku: 'RSV-SKU');

        $this->actingAs(User::query()->first())
            ->post(route('admin.orders.store'), $this->payload($variant, [
                'intent' => 'create',
                'lines' => [
                    ['purchasable_uuid' => $variant->uuid, 'quantity' => 2],
                ],
            ]))
            ->assertRedirect();

        $level = app(InventoryQueryServiceInterface::class)->getStockLevel($variant->uuid);
        $this->assertSame(2, $level->getReserved());
        $this->assertSame(4, $level->getAvailable());
    }

    public function test_inventory_reservation_policy_is_documented(): void
    {
        $path = dirname(__DIR__, 3).'/docs/superpowers/specs/2026-09-04-order-inventory-reservation.md';
        $this->assertFileExists($path);
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('admin create', strtolower($contents));
        $this->assertStringContainsString('confirm', strtolower($contents));
        $this->assertStringContainsString('reserve_on_checkout', $contents);
        $this->assertStringContainsString('draft', strtolower($contents));
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
