<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Customers\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontWishlistApiTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_guest_can_preview_wishlist_items(): void
    {
        $variant = $this->createPurchasableProduct(price: 1200, stock: 5, sku: 'WISH-1');
        $product = $variant->product;

        $response = $this->postJson(route('api.v1.storefront.wishlist.preview'), [
            'items' => [
                ['product_id' => $product->uuid, 'variant_id' => $variant->uuid],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.items.0.product_id', $product->uuid)
            ->assertJsonPath('data.items.0.name', $product->name);
    }

    public function test_authenticated_customer_can_manage_wishlist(): void
    {
        $variant = $this->createPurchasableProduct(price: 900, stock: 3, sku: 'WISH-2');
        $product = $variant->product;
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'customer')
            ->postJson(route('api.v1.storefront.wishlist.items.store'), [
                'product_id' => $product->uuid,
                'variant_id' => $variant->uuid,
            ])
            ->assertCreated()
            ->assertJsonPath('data.count', 1);

        $this->actingAs($customer, 'customer')
            ->postJson(route('api.v1.storefront.wishlist.items.store'), [
                'product_id' => $product->uuid,
                'variant_id' => $variant->uuid,
            ])
            ->assertCreated()
            ->assertJsonPath('data.count', 1);

        $this->actingAs($customer, 'customer')
            ->getJson(route('api.v1.storefront.wishlist.index'))
            ->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.items.0.slug', $product->slug);

        $this->actingAs($customer, 'customer')
            ->deleteJson(route('api.v1.storefront.wishlist.items.destroy'), [
                'product_id' => $product->uuid,
            ])
            ->assertOk()
            ->assertJsonPath('data.count', 0);
    }

    public function test_merge_ignores_duplicates_and_adds_new_items(): void
    {
        $existing = $this->createPurchasableProduct(price: 500, stock: 2, sku: 'WISH-3');
        $incoming = $this->createPurchasableProduct(price: 700, stock: 2, sku: 'WISH-4');
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'customer')
            ->postJson(route('api.v1.storefront.wishlist.items.store'), [
                'product_id' => $existing->product->uuid,
            ])
            ->assertCreated();

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('api.v1.storefront.wishlist.merge'), [
                'items' => [
                    ['product_id' => $existing->product->uuid, 'variant_id' => null],
                    ['product_id' => $incoming->product->uuid, 'variant_id' => $incoming->uuid],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.merged', 1)
            ->assertJsonPath('data.count', 2);
    }

    private function createCustomer(): Customer
    {
        return Customer::query()->create([
            'email' => 'wishlist-user@example.com',
            'name' => 'Wishlist User',
            'password' => 'password123',
            'status' => 'active',
        ]);
    }
}
