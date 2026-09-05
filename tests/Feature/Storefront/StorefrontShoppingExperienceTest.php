<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Customers\Models\Customer;
use Commerce\Customers\Models\CustomerAddress;
use Commerce\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontShoppingExperienceTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedCheckoutDependencies();
    }

    public function test_mini_cart_shows_line_controls_subtotal_and_checkout(): void
    {
        $variant = $this->createPurchasableProduct(price: 2500, stock: 4, sku: 'MINI-1');
        $variant->update([
            'name' => 'Mini Cart Mug / Blue',
            'meta' => ['options' => ['Color' => 'Blue']],
        ]);

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 2,
        ])->assertRedirect();

        $html = $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-drawer="cart"', $html);
        $this->assertStringContainsString('data-qty-stepper', $html);
        $this->assertStringContainsString('data-cart-remove', $html);
        $this->assertStringContainsString(__('storefront::storefront.view_cart'), $html);
        $this->assertStringContainsString(__('storefront::storefront.checkout'), $html);
        $this->assertStringContainsString($variant->product->name, $html);
        $this->assertStringContainsString('Blue', $html);
        $this->assertStringContainsString('data-cart-count', $html);
        $this->assertStringContainsString(route('storefront.products.show', $variant->product->slug), $html);
    }

    public function test_cart_page_has_product_link_stepper_and_sticky_summary(): void
    {
        $variant = $this->createPurchasableProduct(price: 1500, stock: 4, sku: 'CART-UX-1');

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);

        $html = $this->get(route('storefront.cart.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('storefront.products.show', $variant->product->slug), $html);
        $this->assertStringContainsString('data-qty-stepper', $html);
        $this->assertStringContainsString('storefront-cart__summary--sticky', $html);
        $this->assertStringContainsString('storefront-checkout-progress', $html);
        $this->assertStringContainsString($variant->product->name, $html);
    }

    public function test_checkout_has_progress_shipping_cards_and_address_edit(): void
    {
        $this->registerCustomer();
        $this->storeAddress([
            'label' => 'Home',
            'type' => 'shipping',
            'line1' => '123 Sukhumvit',
            'city' => 'Phra Khanong',
            'state' => 'Bangkok',
            'district' => 'Phra Khanong',
            'subdistrict' => 'Phra Khanong',
            'postal_code' => '10110',
            'country_code' => 'TH',
            'is_default_shipping' => '1',
        ]);
        $this->addProductToCart();

        $html = $this->get(route('storefront.checkout'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storefront-checkout-progress', $html);
        $this->assertStringContainsString(__('storefront::storefront.checkout_step_cart'), $html);
        $this->assertStringContainsString(__('storefront::storefront.checkout_step_checkout'), $html);
        $this->assertStringContainsString(__('storefront::storefront.checkout_step_payment'), $html);
        $this->assertStringContainsString(__('storefront::storefront.checkout_step_complete'), $html);
        $this->assertStringContainsString('data-edit-address', $html);
        $this->assertStringContainsString(__('storefront::storefront.edit_address'), $html);
        $this->assertStringNotContainsString(__('storefront::storefront.use_address'), $html);
        $this->assertStringContainsString(__('storefront::storefront.add_new_address'), $html);
        $this->assertStringContainsString(__('storefront::storefront.contact_information'), $html);
        $this->assertStringContainsString('jane@example.com', $html);
        $this->assertStringContainsString(__('storefront::storefront.recipient_name'), $html);
        $this->assertStringContainsString('storefront-shipping-card', $html);
        $this->assertStringContainsString('storefront-checkout__actions', $html);
        $this->assertStringContainsString('storefront-checkout__summary--sticky', $html);
        $this->assertStringContainsString(__('storefront::storefront.payment_next_step_hint'), $html);
        $this->assertStringContainsString(__('storefront::storefront.payment_method_card'), $html);
        $this->assertStringContainsString('data-thailand-address', $html);
        $this->assertStringContainsString(__('storefront::storefront.province'), $html);
        $this->assertStringContainsString(__('storefront::storefront.district'), $html);
        $this->assertStringContainsString(__('storefront::storefront.subdistrict'), $html);
        $this->assertStringContainsString(__('storefront::storefront.address_house_street'), $html);
        $this->assertStringContainsString(__('storefront::storefront.sku'), $html);
    }

    public function test_confirmation_shows_success_items_and_actions(): void
    {
        $variant = $this->createPurchasableProduct(price: 2500, stock: 10);
        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);
        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());

        $order = Order::query()->first();
        $this->assertNotNull($order);

        $html = $this->get(route('storefront.checkout.confirmation', $order))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storefront-confirmation--success', $html);
        $this->assertStringContainsString($order->order_number, $html);
        $this->assertStringContainsString($variant->product->name, $html);
        $this->assertStringContainsString('123 Main St', $html);
        $this->assertStringContainsString(__('storefront::storefront.view_order'), $html);
        $this->assertStringContainsString(__('storefront::storefront.continue_shopping'), $html);
        $this->assertStringContainsString('storefront-checkout-progress', $html);
    }

    public function test_customer_can_edit_address_in_address_book(): void
    {
        $this->registerCustomer();
        $address = $this->storeAddress([
            'label' => 'Home',
            'type' => 'both',
            'line1' => '10 Home Rd',
            'city' => 'Bangkok',
            'postal_code' => '10110',
            'country_code' => 'TH',
        ]);

        $this->get(route('storefront.account.addresses.edit', $address))
            ->assertOk()
            ->assertSee('10 Home Rd')
            ->assertSee(__('storefront::storefront.edit_address'));

        $this->put(route('storefront.account.addresses.update', $address), [
            'label' => 'Condo',
            'type' => 'both',
            'line1' => '99 New Rd',
            'city' => 'Chiang Mai',
            'state' => 'Chiang Mai',
            'district' => 'Mueang Chiang Mai',
            'subdistrict' => 'Si Phum',
            'postal_code' => '50200',
            'country_code' => 'TH',
        ])->assertRedirect(route('storefront.account.addresses'));

        $this->assertDatabaseHas('customer_addresses', [
            'uuid' => $address->uuid,
            'label' => 'Condo',
            'line1' => '99 New Rd',
            'district' => 'Mueang Chiang Mai',
            'subdistrict' => 'Si Phum',
        ]);
    }

    public function test_thailand_location_api_returns_structured_geography(): void
    {
        $provinces = $this->getJson(route('api.v1.storefront.locations.thailand.provinces'))
            ->assertOk()
            ->json('data');

        $this->assertIsArray($provinces);
        $bangkok = collect($provinces)->firstWhere('name_en', 'Bangkok');
        $this->assertNotNull($bangkok);

        $districts = $this->getJson(route('api.v1.storefront.locations.thailand.districts', $bangkok['id']))
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($districts);
        $districtId = $districts[0]['id'];

        $subdistricts = $this->getJson(route('api.v1.storefront.locations.thailand.subdistricts', $districtId))
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($subdistricts);
        $this->assertArrayHasKey('postal_code', $subdistricts[0]);
    }

    public function test_address_book_form_uses_thailand_location_fields(): void
    {
        $this->registerCustomer();

        $html = $this->get(route('storefront.account.addresses'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-thailand-address', $html);
        $this->assertStringContainsString(__('storefront::storefront.province'), $html);
        $this->assertStringContainsString(__('storefront::storefront.district'), $html);
        $this->assertStringContainsString(__('storefront::storefront.subdistrict'), $html);
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

        $this->post(route('storefront.account.register.store'), $payload)->assertRedirect();

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
