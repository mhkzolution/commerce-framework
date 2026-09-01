<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Catalog\DTO\CreateCategoryData;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Services\CategoryService;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontSearchApiTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_search_api_returns_matching_products_and_catalog_items(): void
    {
        $category = app(CategoryService::class)->create(new CreateCategoryData(
            name: 'Nike Running',
            slug: 'nike-running',
            isActive: true,
        ));

        Brand::query()->create([
            'name' => 'Nike Sport',
            'slug' => 'nike-sport',
            'is_active' => true,
        ]);

        $product = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: 'Nike Air Zoom',
            status: 'published',
            visibility: 'public',
            sku: 'NIKE-AIR-001',
            price: 4500,
        ));
        $variant = $product->defaultVariant();
        $product->categories()->attach($category->id);
        app(InventoryServiceInterface::class)->receive($variant->uuid, 5);

        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));
        Cache::flush();

        $response = $this->getJson(route('api.v1.storefront.search', ['q' => 'Nike']));

        $response->assertOk()
            ->assertJsonPath('data.products.0.name', 'Nike Air Zoom')
            ->assertJsonFragment(['name' => 'Nike Running'])
            ->assertJsonFragment(['name' => 'Nike Sport']);
    }

    public function test_search_api_returns_empty_products_for_short_queries(): void
    {
        $variant = $this->createPurchasableProduct(price: 100, stock: 5, sku: 'SHORT-1');
        app(ProductSearchIndexer::class)->index($variant->product->fresh(['variants', 'categories']));

        $this->getJson(route('api.v1.storefront.search', ['q' => 'a']))
            ->assertOk()
            ->assertJsonPath('data.products', []);
    }
}
