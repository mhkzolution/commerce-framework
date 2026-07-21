<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class CustomerAccountTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCheckoutDependencies();
    }

    public function test_customer_can_update_profile(): void
    {
        $this->post(route('storefront.account.register.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('storefront.account'));

        $this->put(route('storefront.account.profile.update'), [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+66123456789',
        ])->assertRedirect();

        $this->get(route('storefront.account'))
            ->assertOk()
            ->assertSee('Jane Smith')
            ->assertSee('jane.smith@example.com');
    }

    public function test_customer_can_view_own_order_detail(): void
    {
        $this->post(route('storefront.account.register.store'), [
            'name' => 'Order Viewer',
            'email' => 'viewer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $variant = $this->createPurchasableProduct(price: 2000, stock: 5);

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);

        $this->post(route('storefront.checkout.store'), array_merge($this->checkoutPayload(), [
            'customer_email' => 'viewer@example.com',
            'customer_name' => 'Order Viewer',
        ]));

        $order = Order::query()->where('customer_email', 'viewer@example.com')->first();
        $this->assertNotNull($order);

        $this->get(route('storefront.account.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $this->post(route('storefront.account.register.store'), [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $variant = $this->createPurchasableProduct();
        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);
        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());

        $order = Order::query()->first();
        $this->post(route('storefront.account.logout'));

        $this->post(route('storefront.account.register.store'), [
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->get(route('storefront.account.orders.show', $order))
            ->assertNotFound();
    }
}
