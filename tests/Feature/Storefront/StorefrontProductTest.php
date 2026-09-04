<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontProductTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_product_detail_page_is_accessible(): void
    {
        $variant = $this->createPurchasableProduct(price: 3200, stock: 3, sku: 'DETAIL-001');
        $product = $variant->product;

        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $this->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee(__('storefront::storefront.add_to_cart'));
    }

    public function test_unknown_product_returns_404(): void
    {
        $this->get('/products/does-not-exist')->assertNotFound();
    }

    public function test_shop_search_finds_indexed_product(): void
    {
        $variant = $this->createPurchasableProduct(price: 1500, stock: 2, sku: 'SHOP-SEARCH-99');
        $product = $variant->product;

        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $this->get(route('storefront.shop.index', ['search' => 'SHOP-SEARCH']))
            ->assertOk()
            ->assertSee($product->name);
    }
}
