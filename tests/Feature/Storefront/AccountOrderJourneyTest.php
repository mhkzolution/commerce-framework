<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Customers\Models\Customer;
use Commerce\Customers\Models\CustomerAddress;
use Commerce\Orders\Models\Order;
use Commerce\Orders\Models\OrderShipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class AccountOrderJourneyTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedCheckoutDependencies();
    }

    public function test_order_detail_shows_timeline_and_shipment_tracking(): void
    {
        $this->registerCustomer([
            'name' => 'Order Viewer',
            'email' => 'viewer@example.com',
        ]);
        $order = $this->placeOrder('viewer@example.com', 'Order Viewer');
        $order->update(['status' => 'confirmed']);

        OrderShipment::query()->create([
            'order_id' => $order->id,
            'status' => OrderShipment::STATUS_SHIPPED,
            'carrier' => 'Kerry Express',
            'tracking_number' => 'KY123456',
            'tracking_url' => 'https://track.example/KY123456',
            'shipped_at' => now(),
        ]);

        $html = $this->get(route('storefront.account.orders.show', $order))
            ->assertOk()
            ->assertSee('storefront-order-timeline', false)
            ->assertSee(__('storefront::storefront.order_step_created'))
            ->assertSee(__('storefront::storefront.order_step_confirmed'))
            ->assertSee(__('storefront::storefront.order_step_processing'))
            ->assertSee(__('storefront::storefront.order_step_shipped'))
            ->assertSee(__('storefront::storefront.order_step_completed'))
            ->assertSee('Kerry Express')
            ->assertSee('KY123456')
            ->assertSee(__('storefront::storefront.track_shipment'))
            ->assertSee('https://track.example/KY123456', false)
            ->assertSee(__('storefront::storefront.reorder'))
            ->getContent();

        $this->assertStringContainsString('storefront-order-timeline__step', $html);
    }

    public function test_dashboard_shows_last_order_date_and_counts(): void
    {
        $this->registerCustomer([
            'name' => 'Order Viewer',
            'email' => 'viewer@example.com',
        ]);
        $order = $this->placeOrder('viewer@example.com', 'Order Viewer');

        $this->get(route('storefront.account'))
            ->assertOk()
            ->assertSee($order->created_at?->format('M j, Y'))
            ->assertSee(route('storefront.account.orders'), false)
            ->assertSee(route('storefront.account.wishlist'), false);
    }

    public function test_reorder_adds_available_items_skips_unavailable_and_redirects_to_cart(): void
    {
        $this->registerCustomer([
            'name' => 'Reorder Shopper',
            'email' => 'reorder@example.com',
        ]);

        $available = $this->createPurchasableProduct(price: 1500, stock: 4, sku: 'REORDER-OK');
        $gone = $this->createPurchasableProduct(price: 900, stock: 1, sku: 'REORDER-GONE');

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $available->uuid,
            'quantity' => 1,
        ]);
        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $gone->uuid,
            'quantity' => 1,
        ]);
        $this->post(route('storefront.checkout.store'), array_merge($this->checkoutPayload(), [
            'customer_email' => 'reorder@example.com',
            'customer_name' => 'Reorder Shopper',
        ]));

        $order = Order::query()->where('customer_email', 'reorder@example.com')->first();
        $this->assertNotNull($order);

        $gone->product->update(['status' => 'archived']);

        $this->post(route('storefront.account.orders.reorder', $order))
            ->assertRedirect(route('storefront.cart.index'))
            ->assertSessionHas('status');

        $html = $this->get(route('storefront.cart.index'))
            ->assertOk()
            ->assertSee($available->product->name)
            ->getContent();

        $this->assertStringNotContainsString($gone->product->name, $html);
    }

    public function test_customer_can_mark_independent_shipping_and_billing_defaults(): void
    {
        $this->registerCustomer();

        $this->post(route('storefront.account.addresses.store'), [
            'label' => 'Home',
            'type' => 'both',
            'line1' => '10 Home Rd',
            'city' => 'Bangkok',
            'postal_code' => '10110',
            'country_code' => 'TH',
            'is_default_shipping' => '1',
        ])->assertRedirect();

        $this->post(route('storefront.account.addresses.store'), [
            'label' => 'Office',
            'type' => 'both',
            'line1' => '20 Office Rd',
            'city' => 'Bangkok',
            'postal_code' => '10500',
            'country_code' => 'TH',
            'is_default_billing' => '1',
        ])->assertRedirect();

        $home = CustomerAddress::query()->where('line1', '10 Home Rd')->first();
        $office = CustomerAddress::query()->where('line1', '20 Office Rd')->first();
        $this->assertNotNull($home);
        $this->assertNotNull($office);
        $this->assertTrue((bool) $home->is_default_shipping);
        $this->assertFalse((bool) $home->is_default_billing);
        $this->assertTrue((bool) $office->is_default_billing);

        $this->post(route('storefront.account.addresses.default-shipping', $office))
            ->assertRedirect(route('storefront.account.addresses'));

        $this->assertFalse((bool) $home->fresh()->is_default_shipping);
        $this->assertTrue((bool) $office->fresh()->is_default_shipping);
        $this->assertTrue((bool) $office->fresh()->is_default_billing);
    }

    /**
     * @param  array{name?: string, email?: string, password?: string}  $overrides
     */
    private function registerCustomer(array $overrides = []): Customer
    {
        $payload = [
            'name' => $overrides['name'] ?? 'Jane Doe',
            'email' => $overrides['email'] ?? 'jane@example.com',
            'password' => $overrides['password'] ?? 'password123',
            'password_confirmation' => $overrides['password'] ?? 'password123',
        ];

        $this->post(route('storefront.account.register.store'), $payload)
            ->assertRedirect();

        $customer = Customer::query()->where('email', $payload['email'])->first();
        $this->assertNotNull($customer);

        return $customer;
    }

    private function placeOrder(string $email, string $name): Order
    {
        $variant = $this->createPurchasableProduct(price: 2000, stock: 5);

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);

        $this->post(route('storefront.checkout.store'), array_merge($this->checkoutPayload(), [
            'customer_email' => $email,
            'customer_name' => $name,
        ]));

        $order = Order::query()->where('customer_email', $email)->first();
        $this->assertNotNull($order);

        return $order;
    }
}
