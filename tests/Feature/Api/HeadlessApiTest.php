<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Commerce\Customers\Models\Customer;
use Commerce\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\Concerns\UsesApiCart;
use Tests\TestCase;

final class HeadlessApiTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;
    use UsesApiCart;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCheckoutDependencies();
        config([
            'payment.gateway' => 'simulated',
            'payment.simulate_gateway' => true,
        ]);
    }

    public function test_headless_checkout_returns_order_payment_and_initiation(): void
    {
        $variant = $this->createPurchasableProduct(price: 42, stock: 10, sku: 'API-HEADLESS');

        $this->addToApiCart($variant->uuid, 1);

        $response = $this->postJson(route('api.v1.cart.checkout'), $this->checkoutPayload())
            ->assertCreated()
            ->assertJsonPath('data.order.status', 'pending')
            ->assertJsonStructure([
                'data' => [
                    'order' => ['uuid', 'order_number', 'grand_total'],
                    'payment' => ['uuid', 'status', 'amount'],
                    'payment_initiation' => ['reference'],
                ],
            ]);

        $paymentUuid = $response->json('data.payment.uuid');
        $this->assertNotNull($paymentUuid);

        $this->postJson(route('api.v1.payments.pay', $paymentUuid))
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $order = Order::query()->first();
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_payment_config_endpoint_lists_gateways(): void
    {
        $this->getJson(route('api.v1.payments.config'))
            ->assertOk()
            ->assertJsonPath('data.gateway', 'simulated')
            ->assertJsonStructure(['data' => ['gateways', 'simulate_enabled']]);
    }

    public function test_products_api_supports_search_query(): void
    {
        $this->createPurchasableProduct(price: 10, stock: 1, sku: 'UNIQUE-SEARCH-SKU');

        $this->getJson('/api/v1/products?search=UNIQUE-SEARCH')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_customer_can_be_updated_via_api(): void
    {
        $customer = Customer::query()->create([
            'email' => 'api-customer@example.com',
            'name' => 'API Customer',
            'status' => 'active',
        ]);

        $this->patchJson(route('api.v1.customers.update', $customer), [
            'email' => 'api-customer@example.com',
            'name' => 'Updated API Customer',
            'status' => 'active',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated API Customer');
    }

    public function test_order_can_be_cancelled_via_api(): void
    {
        $variant = $this->createPurchasableProduct(price: 20, stock: 5, sku: 'CANCEL-API');

        $this->addToApiCart($variant->uuid, 1);

        $checkout = $this->postJson(route('api.v1.cart.checkout'), $this->checkoutPayload())->assertCreated();
        $orderUuid = $checkout->json('data.order.uuid');

        $this->postJson(route('api.v1.orders.cancel', $orderUuid))
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }
}
