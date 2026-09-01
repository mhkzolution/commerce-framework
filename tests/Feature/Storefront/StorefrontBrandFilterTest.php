<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Catalog\Models\Brand;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontBrandFilterTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_brands_index_lists_active_brands(): void
    {
        Brand::query()->create([
            'uuid' => (string) str()->uuid(),
            'name' => 'Alpha Brand',
            'slug' => 'alpha-brand',
            'is_active' => true,
        ]);

        Brand::query()->create([
            'uuid' => (string) str()->uuid(),
            'name' => 'Hidden Brand',
            'slug' => 'hidden-brand',
            'is_active' => false,
        ]);

        $this->get(route('storefront.catalog.brands.index'))
            ->assertOk()
            ->assertSee('Alpha Brand')
            ->assertDontSee('Hidden Brand');
    }

    public function test_brand_route_renders_dedicated_landing_page(): void
    {
        $brand = Brand::query()->create([
            'uuid' => (string) str()->uuid(),
            'name' => 'Acme Kids',
            'slug' => 'acme-kids',
            'description' => 'Quality kids wear',
            'is_active' => true,
        ]);

        $this->get(route('storefront.catalog.brands.show', $brand->slug))
            ->assertOk()
            ->assertSee('Acme Kids')
            ->assertSee('Quality kids wear');
    }

    public function test_brand_landing_page_filters_products(): void
    {
        $brand = Brand::query()->create([
            'uuid' => (string) str()->uuid(),
            'name' => 'Tiny Threads',
            'slug' => 'tiny-threads',
            'is_active' => true,
        ]);

        $inBrand = $this->createPurchasableProduct(price: 100, stock: 3, sku: 'BRAND-IN-001');
        $outside = $this->createPurchasableProduct(price: 100, stock: 3, sku: 'BRAND-OUT-001');

        $inBrand->product->update(['brand_uuid' => $brand->uuid]);

        app(ProductSearchIndexer::class)->index($inBrand->product->fresh(['variants', 'categories']));
        app(ProductSearchIndexer::class)->index($outside->product->fresh(['variants', 'categories']));

        $this->get(route('storefront.catalog.brands.show', $brand->slug))
            ->assertOk()
            ->assertSee($inBrand->product->name)
            ->assertDontSee($outside->product->name);
    }

    public function test_shop_filters_products_by_brand_query_param(): void
    {
        $brand = Brand::query()->create([
            'uuid' => (string) str()->uuid(),
            'name' => 'Outlet Brand',
            'slug' => 'outlet-brand',
            'is_active' => true,
        ]);

        $inBrand = $this->createPurchasableProduct(price: 100, stock: 3, sku: 'BRAND-SHOP-001');
        $outside = $this->createPurchasableProduct(price: 100, stock: 3, sku: 'BRAND-SHOP-002');

        $inBrand->product->update(['brand_uuid' => $brand->uuid]);

        app(ProductSearchIndexer::class)->index($inBrand->product->fresh(['variants', 'categories']));
        app(ProductSearchIndexer::class)->index($outside->product->fresh(['variants', 'categories']));

        $this->get(route('storefront.shop.index', ['brand' => $brand->slug]))
            ->assertOk()
            ->assertSee($inBrand->product->name)
            ->assertDontSee($outside->product->name);
    }
}
