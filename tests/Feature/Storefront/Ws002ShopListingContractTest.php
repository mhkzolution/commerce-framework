<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Catalog\Models\Category as CatalogCategory;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class Ws002ShopListingContractTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_shop_listing_uses_page_container_toolbar_and_empty_state(): void
    {
        $html = $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('storefront-page-container', false)
            ->assertSee('storefront-shop-toolbar', false)
            ->assertSee('storefront-empty', false)
            ->assertDontSee('x-admin.search-input', false)
            ->getContent();

        $this->assertStringContainsString('storefront-shop-main', $html);
        $this->assertStringNotContainsString('defaultVariant', $html);
    }

    public function test_shop_honors_category_query_and_shared_product_card(): void
    {
        $mugs = CatalogCategory::query()->create([
            'name' => 'Mugs',
            'slug' => 'mugs',
            'is_active' => true,
            'position' => 1,
        ]);

        $inMugs = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: 'Harbor Listing Mug',
            status: 'published',
            visibility: 'public',
            sku: 'LIST-MUG-1',
            price: 2100,
            categoryIds: [$mugs->id],
        ));
        $variant = $inMugs->defaultVariant();
        $this->assertNotNull($variant);
        app(InventoryServiceInterface::class)->receive($variant->uuid, 4);

        $other = $this->createPurchasableProduct(price: 1500, stock: 2, sku: 'LIST-OTHER-1');

        $this->get(route('storefront.shop.index', ['category' => 'mugs']))
            ->assertOk()
            ->assertSee('Harbor Listing Mug')
            ->assertSee('storefront-product-card', false)
            ->assertDontSee($other->product->name);
    }

    public function test_shop_search_still_finds_indexed_product(): void
    {
        $variant = $this->createPurchasableProduct(price: 1500, stock: 2, sku: 'SHOP-SEARCH-11');
        $product = $variant->product;
        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $this->get(route('storefront.shop.index', ['search' => 'SHOP-SEARCH']))
            ->assertOk()
            ->assertSee($product->name);
    }
}
