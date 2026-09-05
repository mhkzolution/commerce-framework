<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use Commerce\Customers\Models\Customer;
use Commerce\Customers\Models\CustomerAddress;
use Commerce\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class CheckoutAccountIntegrationTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedCheckoutDependencies();
    }

    public function test_authenticated_checkout_shows_saved_address_cards_and_defaults(): void
    {
        $this->registerCustomer();
        $home = $this->storeAddress([
            'label' => 'Home',
            'type' => 'shipping',
            'line1' => '123 Sukhumvit',
            'city' => 'Bangkok',
            'postal_code' => '10110',
            'country_code' => 'TH',
            'is_default_shipping' => '1',
        ]);
        $office = $this->storeAddress([
            'label' => 'Office',
            'type' => 'billing',
            'line1' => '456 Silom Road',
            'city' => 'Bangkok',
            'postal_code' => '10500',
            'country_code' => 'TH',
            'is_default_billing' => '1',
        ]);

        $this->addProductToCart();

        $html = $this->get(route('storefront.checkout'))
            ->assertOk()
            ->assertSee('storefront-address-card', false)
            ->assertDontSee('data-use-address', false)
            ->assertSee('Home')
            ->assertSee('123 Sukhumvit')
            ->assertSee('Office')
            ->assertSee('456 Silom Road')
            ->getContent();

        $this->assertStringContainsString('data-address-uuid="'.$home->uuid.'"', $html);
        $this->assertStringContainsString('data-selected="1"', $html);
        $this->assertStringContainsString(__('storefront::storefront.add_new_address'), $html);
        $this->assertStringContainsString(__('storefront::storefront.contact_information'), $html);
        $this->assertStringContainsString('jane@example.com', $html);
        $this->assertStringNotContainsString(__('storefront::storefront.use_address'), $html);
        $this->assertStringContainsString(__('storefront::storefront.save_to_address_book'), $html);
        $this->assertStringContainsString(__('storefront::storefront.use_once'), $html);
    }

    public function test_checkout_uses_saved_shipping_address(): void
    {
        $this->registerCustomer();
        $address = $this->storeAddress([
            'label' => 'Home',
            'type' => 'shipping',
            'line1' => '123 Sukhumvit',
            'city' => 'Bangkok',
            'postal_code' => '10110',
            'country_code' => 'TH',
            'is_default_shipping' => '1',
        ]);

        $this->addProductToCart();

        $this->post(route('storefront.checkout.store'), array_merge($this->checkoutPayload(), [
            'shipping_address_uuid' => $address->uuid,
            'billing_same_as_shipping' => '1',
        ]))->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame('123 Sukhumvit', $order->shipping_address['line1'] ?? null);
        $this->assertSame('Bangkok', $order->shipping_address['city'] ?? null);
    }

    public function test_new_checkout_address_can_be_saved_or_used_once(): void
    {
        $this->registerCustomer();
        $this->addProductToCart();

        $this->post(route('storefront.checkout.store'), array_merge($this->checkoutPayload(), [
            'shipping_address' => [
                'line1' => '88 New Road',
                'city' => 'Chiang Mai',
                'postal_code' => '50000',
                'country_code' => 'TH',
            ],
            'save_shipping_address' => '1',
            'shipping_address_label' => 'Cabin',
        ]))->assertRedirect();

        $this->assertDatabaseHas('customer_addresses', [
            'line1' => '88 New Road',
            'label' => 'Cabin',
        ]);

        $this->addProductToCart();

        $this->post(route('storefront.checkout.store'), array_merge($this->checkoutPayload(), [
            'shipping_address' => [
                'line1' => 'One-time Lane',
                'city' => 'Phuket',
                'postal_code' => '83000',
                'country_code' => 'TH',
            ],
        ]))->assertRedirect();

        $this->assertDatabaseMissing('customer_addresses', [
            'line1' => 'One-time Lane',
        ]);
    }

    public function test_login_from_checkout_returns_to_checkout_not_account_or_admin(): void
    {
        $this->registerCustomer([
            'email' => 'return.checkout@example.com',
        ]);
        $this->post(route('storefront.account.logout'));
        $this->addProductToCart();

        $this->get(route('storefront.account.login', [
            'redirect' => route('storefront.checkout'),
        ]))->assertOk();

        $this->post(route('storefront.account.login.store'), [
            'email' => 'return.checkout@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('storefront.checkout'));
    }

    public function test_register_from_checkout_restores_draft_and_cart(): void
    {
        $this->addProductToCart();

        $this->post(route('storefront.checkout.draft'), [
            'customer_name' => 'Draft Shopper',
            'customer_email' => 'draft.shopper@example.com',
            'shipping_address' => [
                'line1' => '99 Draft Street',
                'city' => 'Bangkok',
                'postal_code' => '10330',
                'country_code' => 'TH',
            ],
        ])->assertRedirect();

        $this->get(route('storefront.account.register', [
            'redirect' => route('storefront.checkout'),
        ]))->assertOk();

        $this->post(route('storefront.account.register.store'), [
            'name' => 'Draft Shopper',
            'email' => 'draft.shopper@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('storefront.checkout'));

        $this->get(route('storefront.checkout'))
            ->assertOk()
            ->assertSee('99 Draft Street')
            ->assertSee('Test Product', false);
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeAddress(array $payload): CustomerAddress
    {
        $this->post(route('storefront.account.addresses.store'), array_merge([
            'type' => 'shipping',
            'city' => 'Bangkok',
            'postal_code' => '10110',
            'country_code' => 'TH',
        ], $payload))->assertRedirect();

        $address = CustomerAddress::query()->where('line1', $payload['line1'])->latest('id')->first();
        $this->assertNotNull($address);

        return $address;
    }

    private function addProductToCart(): void
    {
        $variant = $this->createPurchasableProduct();

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);
    }
}
