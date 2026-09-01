<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use Commerce\Cart\Models\CartToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\Concerns\UsesApiCart;
use Tests\TestCase;

final class CartTokenTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;
    use UsesApiCart;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCheckoutDependencies();
    }

    public function test_cart_token_is_issued_on_first_mutation_and_persists_cart(): void
    {
        $variant = $this->createPurchasableProduct(price: 15, stock: 5, sku: 'TOKEN-CART');

        $this->addToApiCart($variant->uuid, 2)
            ->assertHeader((string) config('cart.token_header', 'X-Cart-Token'));

        $this->assertNotNull($this->cartToken);
        $this->assertDatabaseHas('cart_tokens', ['uuid' => $this->cartToken]);

        $this->getJson(route('api.v1.cart.show'))
            ->assertOk()
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('data.lines.0.purchasable_uuid', $variant->uuid);
    }

    public function test_invalid_cart_token_returns_not_found(): void
    {
        $this->withHeader((string) config('cart.token_header', 'X-Cart-Token'), '00000000-0000-4000-8000-000000000000')
            ->getJson(route('api.v1.cart.show'))
            ->assertNotFound()
            ->assertJsonPath('error.code', 'cart.invalid_token');
    }

    public function test_expired_cart_token_is_rejected(): void
    {
        $token = CartToken::issue();
        $token->update(['expires_at' => now()->subMinute()]);

        $this->withHeader((string) config('cart.token_header', 'X-Cart-Token'), $token->uuid)
            ->getJson(route('api.v1.cart.show'))
            ->assertNotFound();
    }
}
