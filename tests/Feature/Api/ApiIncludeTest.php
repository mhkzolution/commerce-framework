<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Commerce\Customers\Models\Customer;
use Commerce\Customers\Models\CustomerAddress;
use Commerce\Orders\Models\Order;
use Commerce\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ApiIncludeTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_product_show_can_include_variants_and_categories(): void
    {
        $variant = $this->createPurchasableProduct(price: 30, stock: 3, sku: 'INCLUDE-API');
        $product = Product::query()->where('uuid', $variant->product->uuid)->firstOrFail();

        $this->getJson(route('api.v1.products.show', $product->slug).'?include=variants,categories')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'uuid',
                    'variants' => [['uuid', 'sku', 'price']],
                    'categories',
                ],
            ]);
    }

    public function test_product_show_omits_unrequested_relations_by_default(): void
    {
        $variant = $this->createPurchasableProduct(price: 30, stock: 3, sku: 'INCLUDE-DEFAULT');
        $product = Product::query()->where('uuid', $variant->product->uuid)->firstOrFail();

        $response = $this->getJson(route('api.v1.products.show', $product->slug))
            ->assertOk();

        $this->assertArrayNotHasKey('variants', $response->json('data'));
        $this->assertArrayNotHasKey('categories', $response->json('data'));
    }

    public function test_order_show_can_include_line_items(): void
    {
        $order = Order::query()->create([
            'order_number' => 'ORD-INCLUDE-1',
            'status' => 'pending',
            'currency' => 'USD',
            'subtotal' => 1000,
            'discount_total' => 0,
            'tax_total' => 0,
            'shipping_total' => 0,
            'grand_total' => 1000,
            'customer_email' => 'include@example.com',
            'customer_name' => 'Include Test',
        ]);

        $order->lineItems()->create([
            'purchasable_uuid' => '00000000-0000-4000-8000-000000000001',
            'name' => 'Included line',
            'sku' => 'INC-1',
            'quantity' => 1,
            'unit_price' => 1000,
            'line_total' => 1000,
        ]);

        $this->getJson(route('api.v1.orders.show', $order->uuid).'?include=line_items')
            ->assertOk()
            ->assertJsonCount(1, 'data.line_items');
    }

    public function test_customer_show_can_include_addresses(): void
    {
        $customer = Customer::query()->create([
            'email' => 'include-customer@example.com',
            'name' => 'Include Customer',
            'status' => 'active',
        ]);

        CustomerAddress::query()->create([
            'customer_id' => $customer->id,
            'type' => 'shipping',
            'line1' => '123 Include St',
            'city' => 'Bangkok',
            'postal_code' => '10110',
            'country_code' => 'TH',
            'is_default' => true,
        ]);

        $this->getJson(route('api.v1.customers.show', $customer->uuid).'?include=addresses')
            ->assertOk()
            ->assertJsonCount(1, 'data.addresses')
            ->assertJsonPath('data.addresses.0.line1', '123 Include St');
    }
}
