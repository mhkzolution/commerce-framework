<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Cart\Services\HomepageProductQuery;
use Commerce\Contracts\Storefront\ProductCardData;
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
    }

    public function test_product_card_data_type_is_the_shared_contract(): void
    {
        $this->createPurchasableProduct(price: 1250, stock: 8, sku: 'CARD-DTO-1');

        $cards = app(HomepageProductQuery::class)->arrivals();
        $this->assertNotEmpty($cards);
        $this->assertInstanceOf(ProductCardData::class, $cards[0]);
    }
}
