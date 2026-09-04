<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use Commerce\Customers\Contracts\CustomerAddressServiceInterface;
use Commerce\Customers\Contracts\CustomerServiceInterface;
use Commerce\Customers\DTO\CreateAddressData;
use Commerce\Customers\DTO\CreateCustomerData;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class AdminOrderCreateTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(IamSeeder::class);
    }

    public function test_create_page_is_a_lookup_workflow_and_does_not_dump_the_catalog(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'HIDDEN-SKU-9999');

        $html = $this->actingAs(User::query()->first())
            ->get(route('admin.orders.create'))
            ->assertOk()
            ->assertSee('data-order-create', false)
            ->assertSee(route('admin.orders.lookup.products'), false)
            ->assertSee(route('admin.orders.lookup.customers'), false)
            ->assertSee('name="customer_phone"', false)
            ->assertSee('name="shipping_address[line1]"', false)
            ->assertSee('name="discount_type"', false)
            ->assertSee('name="shipping_fee"', false)
            ->assertSee('name="intent"', false)
            ->assertDontSee('HIDDEN-SKU-9999', false)
            ->assertDontSee('— Select variant —', false)
            ->assertDontSee('lines[0][purchasable_uuid]', false)
            ->getContent();

        $this->assertStringContainsString('order-create-layout', $html);
        $this->assertStringContainsString('order-create-summary', $html);
        $this->assertStringContainsString(__('orders::admin.add_product'), $html);
        $this->assertStringContainsString(__('orders::admin.save_draft'), $html);
        $this->assertStringContainsString(__('orders::admin.create_order'), $html);
        $this->assertSame($variant->sku, 'HIDDEN-SKU-9999');
    }

    public function test_product_lookup_is_async_and_scoped(): void
    {
        $match = $this->createPurchasableProduct(price: 1500, stock: 42, sku: 'LOOKUP-MATCH');
        $this->createPurchasableProduct(sku: 'LOOKUP-OTHER');

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.orders.lookup.products', ['q' => 'LOOKUP-MATCH']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.sku', 'LOOKUP-MATCH')
            ->assertJsonPath('results.0.purchasable_uuid', $match->uuid)
            ->assertJsonPath('results.0.available', 42)
            ->assertJsonPath('results.0.price', 1500);

        $barcode = $this->createPurchasableProduct(sku: 'BAR-SKU');
        $barcode->forceFill(['meta' => array_merge($barcode->meta ?? [], ['barcode' => '8850999123456'])])->save();

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.orders.lookup.products', ['q' => '8850999123456']))
            ->assertOk()
            ->assertJsonPath('results.0.sku', 'BAR-SKU');
    }

    public function test_customer_lookup_searches_name_email_phone_and_id(): void
    {
        $customer = app(CustomerServiceInterface::class)->create(new CreateCustomerData(
            email: 'harbor@example.com',
            name: 'Harbor Guest',
            phone: '0812345678',
        ));

        app(CustomerAddressServiceInterface::class)->create(new CreateAddressData(
            customerUuid: $customer->uuid,
            line1: '88 Sukhumvit',
            city: 'Khlong Toei',
            postalCode: '10110',
            countryCode: 'TH',
            state: 'Bangkok',
            isDefault: true,
        ));

        $admin = User::query()->first();

        $this->actingAs($admin)
            ->getJson(route('admin.orders.lookup.customers', ['q' => '0812345678']))
            ->assertOk()
            ->assertJsonPath('results.0.uuid', $customer->uuid)
            ->assertJsonPath('results.0.email', 'harbor@example.com')
            ->assertJsonPath('results.0.phone', '0812345678');

        $this->actingAs($admin)
            ->getJson(route('admin.orders.lookup.customers', ['q' => $customer->uuid]))
            ->assertOk()
            ->assertJsonPath('results.0.name', 'Harbor Guest')
            ->assertJsonPath('results.0.address.line1', '88 Sukhumvit');
    }

    public function test_guest_order_requires_phone_and_at_least_one_line(): void
    {
        $variant = $this->createPurchasableProduct();

        $this->actingAs(User::query()->first())
            ->from(route('admin.orders.create'))
            ->post(route('admin.orders.store'), [
                'intent' => 'create',
                'customer_name' => 'Walk-in',
                'customer_email' => 'walkin@example.com',
                'lines' => [
                    ['purchasable_uuid' => $variant->uuid, 'quantity' => 1],
                ],
            ])
            ->assertRedirect(route('admin.orders.create'))
            ->assertSessionHasErrors('customer_phone');

        $this->actingAs(User::query()->first())
            ->from(route('admin.orders.create'))
            ->post(route('admin.orders.store'), [
                'intent' => 'create',
                'customer_name' => 'Walk-in',
                'customer_phone' => '0891112222',
                'lines' => [],
            ])
            ->assertRedirect(route('admin.orders.create'))
            ->assertSessionHasErrors('lines');
    }

    public function test_admin_can_create_order_with_shipping_discount_and_notes(): void
    {
        $variant = $this->createPurchasableProduct(price: 2000, stock: 10, sku: 'ORDER-LINE');

        $this->actingAs(User::query()->first())
            ->post(route('admin.orders.store'), [
                'intent' => 'create',
                'customer_name' => 'Mina Shore',
                'customer_email' => 'mina@example.com',
                'customer_phone' => '0890001111',
                'channel' => 'phone',
                'admin_status' => 'pending',
                'notes' => 'Customer called directly',
                'discount_type' => 'fixed',
                'discount_value' => '5.00',
                'shipping_fee' => '20.00',
                'shipping_address' => [
                    'recipient_name' => 'Mina Shore',
                    'phone' => '0890001111',
                    'line1' => '12 Rama IV',
                    'district' => 'Bang Rak',
                    'province' => 'Bangkok',
                    'postal_code' => '10500',
                ],
                'lines' => [
                    ['purchasable_uuid' => $variant->uuid, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame('Mina Shore', $order->customer_name);
        $this->assertSame('phone', $order->channel);
        $this->assertSame(4000, $order->subtotal);
        $this->assertSame(500, $order->discount_total);
        $this->assertSame(2000, $order->shipping_total);
        $this->assertSame(0, $order->tax_total);
        $this->assertSame(5500, $order->grand_total);
        $this->assertSame('Customer called directly', $order->meta['notes'] ?? null);
        $this->assertSame('0890001111', $order->meta['customer_phone'] ?? null);
        $this->assertSame('12 Rama IV', $order->shipping_address['line1'] ?? null);
        $this->assertSame(2, $order->lineItems()->sum('quantity'));
    }

    public function test_quantity_cannot_exceed_available_stock(): void
    {
        $variant = $this->createPurchasableProduct(stock: 1, sku: 'LOW-STOCK');

        $this->actingAs(User::query()->first())
            ->from(route('admin.orders.create'))
            ->post(route('admin.orders.store'), [
                'intent' => 'create',
                'customer_name' => 'Mina Shore',
                'customer_phone' => '0890001111',
                'lines' => [
                    ['purchasable_uuid' => $variant->uuid, 'quantity' => 5],
                ],
            ])
            ->assertRedirect(route('admin.orders.create'))
            ->assertSessionHasErrors('lines');
    }

    public function test_save_draft_creates_a_pending_order_marked_as_draft(): void
    {
        $variant = $this->createPurchasableProduct();

        $this->actingAs(User::query()->first())
            ->post(route('admin.orders.store'), [
                'intent' => 'draft',
                'customer_name' => 'Draft Buyer',
                'customer_phone' => '0890001111',
                'admin_status' => 'draft',
                'lines' => [
                    ['purchasable_uuid' => $variant->uuid, 'quantity' => 1],
                ],
            ])
            ->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame('pending', $order->status);
        $this->assertSame('draft', $order->meta['admin_status'] ?? null);
    }
}
