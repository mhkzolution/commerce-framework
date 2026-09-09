<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Cart\Services\HomepageProductQuery;
use Commerce\Cart\Services\ProductCardMapper;
use Commerce\Contracts\Storefront\ProductCardData;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class Ws002ProductCardContractTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_shop_listing_renders_shared_product_card_without_eloquent_in_html(): void
    {
        $variant = $this->createPurchasableProduct(price: 2100, stock: 4, sku: 'CARD-SHOP-1');
        $product = $variant->product;
        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $html = $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('storefront-product-card', false)
            ->getContent();

        $this->assertStringNotContainsString('defaultVariant', $html);
        $this->assertStringContainsString('21.00', $html);
    }

    public function test_homepage_arrivals_use_the_same_product_card_class(): void
    {
        $variant = $this->createPurchasableProduct(price: 1250, stock: 8, sku: 'CARD-HOME-1');
        $product = $variant->product;

        $html = $this->get('/')
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('storefront-product-card', false)
            ->getContent();

        $this->assertStringNotContainsString('storefront-home-product-card', $html);
        $this->assertStringNotContainsString('storefront-product-card__stock', $html);
    }

    public function test_homepage_arrivals_omit_out_of_stock_products(): void
    {
        $inStock = $this->createPurchasableProduct(price: 1250, stock: 8, sku: 'CARD-HOME-IN');
        $soldOut = $this->createPurchasableProduct(price: 1250, stock: 1, sku: 'CARD-HOME-OOS');
        app(InventoryServiceInterface::class)->setOnHand($soldOut->uuid, 0);

        $this->get('/')
            ->assertOk()
            ->assertSee($inStock->product->name)
            ->assertDontSee($soldOut->product->name);
    }

    public function test_product_card_data_type_is_the_shared_contract(): void
    {
        $this->createPurchasableProduct(price: 1250, stock: 8, sku: 'CARD-DTO-1');

        $cards = app(HomepageProductQuery::class)->arrivals();
        $this->assertNotEmpty($cards);
        $this->assertInstanceOf(ProductCardData::class, $cards[0]);
    }

    public function test_mapper_returns_null_when_every_variant_is_out_of_stock(): void
    {
        $soldOut = $this->createPurchasableProduct(price: 1250, stock: 1, sku: 'CARD-ALL-OOS');
        app(InventoryServiceInterface::class)->setOnHand($soldOut->uuid, 0);

        $card = app(ProductCardMapper::class)->fromProduct($soldOut->product->fresh('variants'));

        $this->assertNull($card);
    }

    public function test_card_uses_in_stock_sibling_when_default_is_out_of_stock(): void
    {
        $default = $this->createPurchasableProduct(price: 1250, stock: 1, sku: 'CARD-MULTI-OOS');
        app(InventoryServiceInterface::class)->setOnHand($default->uuid, 0);
        $sibling = $default->product->variants()->create([
            'tenant_id' => $default->tenant_id,
            'sku' => 'CARD-MULTI-IN',
            'track_inventory' => true,
            'name' => 'In-stock sibling',
            'price' => 1250,
            'is_default' => false,
            'position' => 1,
        ]);
        app(InventoryServiceInterface::class)->receive($sibling->uuid, 3);

        $card = app(ProductCardMapper::class)->fromProduct($default->product->fresh('variants'));

        $this->assertNotNull($card);
        $this->assertTrue($card->inStock);
        $this->assertSame($sibling->uuid, $card->variantUuid);
        $this->assertSame(3, $card->available);
    }
}
