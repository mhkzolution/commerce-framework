<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontHeaderDrawersTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_cart_drawer_lists_added_line(): void
    {
        $variant = $this->createPurchasableProduct(price: 2500, stock: 4, sku: 'DRAWER-1');

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ])->assertRedirect();

        $html = $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-drawer="cart"', $html);
        $this->assertStringContainsString($variant->product->name, $html);
        $this->assertStringContainsString(__('storefront::storefront.view_cart'), $html);
    }
}
