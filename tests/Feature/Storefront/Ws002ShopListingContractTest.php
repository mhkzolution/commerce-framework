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

    public function test_missing_category_and_in_stock_filter_use_the_shared_empty_state(): void
    {
        app(ProductServiceInterface::class)->create(new CreateProductData(
            name: 'Out Of Stock Tray',
            status: 'published',
            visibility: 'public',
            sku: 'LIST-OOS-1',
            price: 1500,
        ));

        $missing = $this->get(route('storefront.shop.index', ['category' => 'does-not-exist']))
            ->assertOk()
            ->assertSee('storefront-empty', false)
            ->getContent();

        $this->assertStringNotContainsString('Published products', $missing);
        $this->assertStringContainsString('storefront-empty__title', $missing);

        $inStock = $this->get(route('storefront.shop.index', ['availability' => 'in_stock']))
            ->assertOk()
            ->assertSee('storefront-empty', false)
            ->getContent();

        $this->assertStringContainsString('storefront-empty', $inStock);
        $this->assertStringNotContainsString('Published products', $inStock);
    }

    public function test_get_filters_survive_reload_and_pagination_query_string(): void
    {
        $mugs = CatalogCategory::query()->create([
            'name' => 'Mugs',
            'slug' => 'mugs',
            'is_active' => true,
            'position' => 1,
        ]);

        for ($i = 1; $i <= 25; $i++) {
            $product = app(ProductServiceInterface::class)->create(new CreateProductData(
                name: 'Paged Mug '.$i,
                status: 'published',
                visibility: 'public',
                sku: 'PAGE-MUG-'.$i,
                price: 1000 + $i,
                categoryIds: [$mugs->id],
            ));
            $variant = $product->defaultVariant();
            $this->assertNotNull($variant);
            app(InventoryServiceInterface::class)->receive($variant->uuid, 2);
        }

        $reload = $this->get(route('storefront.shop.index', [
            'category' => 'mugs',
            'availability' => 'in_stock',
            'sort' => 'price_asc',
        ]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="mugs"', $reload);
        $this->assertStringContainsString('selected', $reload);
        $this->assertStringContainsString('value="in_stock"', $reload);
        $this->assertStringContainsString('value="price_asc"', $reload);
        $this->assertStringContainsString('storefront-pagination', $reload);

        $pageTwo = $this->get(route('storefront.shop.index', [
            'category' => 'mugs',
            'availability' => 'in_stock',
            'sort' => 'price_asc',
            'page' => 2,
        ]))
            ->assertOk()
            ->assertSee('Paged Mug 25')
            ->getContent();

        $this->assertStringContainsString('category=mugs', $pageTwo);
        $this->assertStringContainsString('availability=in_stock', $pageTwo);
        $this->assertStringContainsString('sort=price_asc', $pageTwo);
    }
}
