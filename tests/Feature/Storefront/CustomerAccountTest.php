<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Customers\Models\Customer;
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
        $this->withoutVite();
        $this->seedCheckoutDependencies();
    }

    public function test_account_dashboard_uses_account_center_nav(): void
    {
        $this->registerCustomer();

        $html = $this->get(route('storefront.account'))
            ->assertOk()
            ->assertSee('storefront-account__layout', false)
            ->assertSee('storefront-account-menu', false)
            ->getContent();

        $this->assertStringContainsString(route('storefront.account.orders'), $html);
        $this->assertStringContainsString(route('storefront.account.addresses'), $html);
        $this->assertStringContainsString(route('storefront.account.wishlist'), $html);
        $this->assertStringContainsString(route('storefront.account.profile'), $html);
        $this->assertStringContainsString(route('storefront.account.security'), $html);
        $this->assertStringNotContainsString('name="line1"', $html);
    }

    public function test_account_pages_are_reachable(): void
    {
        $this->registerCustomer();

        $this->get(route('storefront.account.orders'))->assertOk();
        $this->get(route('storefront.account.addresses'))
            ->assertOk()
            ->assertSee('name="line1"', false);
        $this->get(route('storefront.account.wishlist'))->assertOk();
        $this->get(route('storefront.account.profile'))
            ->assertOk()
            ->assertSee('Jane Doe');
        $this->get(route('storefront.account.security'))->assertOk();
    }

    public function test_guest_account_center_routes_redirect_to_storefront_login(): void
    {
        foreach ([
            'storefront.account.orders',
            'storefront.account.addresses',
            'storefront.account.wishlist',
            'storefront.account.profile',
            'storefront.account.security',
        ] as $name) {
            $this->get(route($name))->assertRedirect(route('storefront.account.login'));
        }
    }

    public function test_customer_can_update_profile(): void
    {
        $this->registerCustomer();

        $this->put(route('storefront.account.profile.update'), [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+66123456789',
        ])->assertRedirect();

        $this->get(route('storefront.account.profile'))
            ->assertOk()
            ->assertSee('Jane Smith')
            ->assertSee('jane.smith@example.com');
    }

    public function test_customer_can_change_password(): void
    {
        $this->registerCustomer();

        $this->put(route('storefront.account.security.password'), [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('storefront.account.security'));

        $this->post(route('storefront.account.logout'));

        $this->post(route('storefront.account.login.store'), [
            'email' => 'jane@example.com',
            'password' => 'newpassword123',
        ])->assertRedirect(route('storefront.account'));
    }

    public function test_customer_can_manage_addresses_from_account(): void
    {
        $this->registerCustomer();

        $this->post(route('storefront.account.addresses.store'), [
            'label' => 'Home',
            'type' => 'shipping',
            'line1' => '42 Market Street',
            'city' => 'Bangkok',
            'postal_code' => '10110',
            'country_code' => 'TH',
            'is_default' => '1',
        ])->assertRedirect();

        $this->get(route('storefront.account.addresses'))
            ->assertOk()
            ->assertSee('42 Market Street')
            ->assertSee('Home');
    }

    public function test_wishlist_page_shows_saved_items(): void
    {
        $this->registerCustomer();
        $variant = $this->createPurchasableProduct(price: 1500, stock: 4, sku: 'ACC-WISH');

        $this->postJson(route('api.v1.storefront.wishlist.items.store'), [
            'product_id' => $variant->product->uuid,
            'variant_id' => $variant->uuid,
        ])->assertCreated();

        $this->get(route('storefront.account.wishlist'))
            ->assertOk()
            ->assertSee($variant->product->name);
    }

    public function test_customer_can_view_orders_index_and_own_order_detail(): void
    {
        $this->registerCustomer([
            'name' => 'Order Viewer',
            'email' => 'viewer@example.com',
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

        $this->get(route('storefront.account.orders'))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->get(route('storefront.account.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $this->registerCustomer([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $variant = $this->createPurchasableProduct();
        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);
        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());

        $order = Order::query()->first();
        $this->post(route('storefront.account.logout'));

        $this->registerCustomer([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ]);

        $this->get(route('storefront.account.orders.show', $order))
            ->assertNotFound();
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
            ->assertRedirect(route('storefront.account'));

        $customer = Customer::query()->where('email', $payload['email'])->first();
        $this->assertNotNull($customer);

        return $customer;
    }
}
