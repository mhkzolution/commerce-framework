<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontProductTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_product_detail_page_is_accessible(): void
    {
        $variant = $this->createPurchasableProduct(price: 32, stock: 3, sku: 'DETAIL-001');
        $product = $variant->product;
        $otherVariant = $this->createPurchasableProduct(price: 18, stock: 2, sku: 'DETAIL-002');

        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));
        app(ProductSearchIndexer::class)->index($otherVariant->product->fresh(['variants', 'categories']));

        $this->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee(__('storefront::storefront.add_to_cart'))
            ->assertSee(__('storefront::storefront.buy_now'))
            ->assertSee(__('storefront::storefront.recently_viewed'), false)
            ->assertSee(__('storefront::storefront.related_products'), false)
            ->assertSee($otherVariant->product->name);
    }

    public function test_unknown_product_returns_404(): void
    {
        $this->get('/products/does-not-exist')->assertNotFound();
    }

    public function test_shop_search_finds_indexed_product(): void
    {
        $variant = $this->createPurchasableProduct(price: 15, stock: 2, sku: 'SHOP-SEARCH-99');
        $product = $variant->product;

        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $this->get(route('storefront.shop.index', ['search' => 'SHOP-SEARCH']))
            ->assertOk()
            ->assertSee($product->name);
    }
}
