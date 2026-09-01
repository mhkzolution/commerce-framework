<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontCatalogApiTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_storefront_catalog_api_lists_active_brands(): void
    {
        $brand = Brand::query()->create([
            'name' => 'Public Brand',
            'slug' => 'public-brand',
            'is_active' => true,
        ]);

        Brand::query()->create([
            'name' => 'Hidden Brand',
            'slug' => 'hidden-brand',
            'is_active' => false,
        ]);

        $this->getJson(route('api.v1.storefront.catalog.brands.index'))
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'public-brand')
            ->assertJsonMissing(['slug' => 'hidden-brand']);
    }

    public function test_storefront_catalog_api_lists_brands_without_products(): void
    {
        Brand::query()->create([
            'name' => 'Empty Brand',
            'slug' => 'empty-brand',
            'is_active' => true,
        ]);

        $this->getJson(route('api.v1.storefront.catalog.brands.index'))
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'empty-brand');
    }

    public function test_storefront_catalog_api_shows_brand_by_slug(): void
    {
        Brand::query()->create([
            'name' => 'Show Brand',
            'slug' => 'show-brand',
            'is_active' => true,
        ]);

        $this->getJson(route('api.v1.storefront.catalog.brands.show', 'show-brand'))
            ->assertOk()
            ->assertJsonPath('data.name', 'Show Brand');
    }

    public function test_storefront_catalog_api_lists_active_categories(): void
    {
        $category = Category::query()->create([
            'name' => 'Kids',
            'slug' => 'kids',
            'is_active' => true,
        ]);

        $variant = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'API-CAT-001');
        $variant->product->categories()->attach($category->id);

        $this->getJson(route('api.v1.storefront.catalog.categories.index'))
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'kids');
    }

    public function test_storefront_catalog_api_lists_collections(): void
    {
        $collection = Collection::query()->create([
            'name' => 'Featured',
            'slug' => 'featured',
            'type' => Collection::TYPE_MANUAL,
        ]);

        $variant = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'API-COL-001');
        $collection->products()->attach($variant->product_id);

        $this->getJson(route('api.v1.storefront.catalog.collections.index'))
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'featured');
    }
}
