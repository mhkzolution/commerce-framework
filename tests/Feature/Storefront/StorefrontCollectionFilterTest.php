<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Catalog\Models\Collection;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontCollectionFilterTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_collection_route_renders_dedicated_landing_page(): void
    {
        $collection = Collection::query()->create([
            'name' => 'Summer Sale',
            'slug' => 'summer-sale',
            'description' => 'Hot deals for the season',
        ]);

        $this->get(route('storefront.catalog.collections.show', $collection->slug))
            ->assertOk()
            ->assertSee('Summer Sale')
            ->assertSee('Hot deals for the season');
    }

    public function test_collection_page_filters_products_by_collection(): void
    {
        $collection = Collection::query()->create([
            'name' => 'Featured',
            'slug' => 'featured',
        ]);

        $inCollection = $this->createPurchasableProduct(price: 100, stock: 3, sku: 'COLL-IN-001');
        $outside = $this->createPurchasableProduct(price: 100, stock: 3, sku: 'COLL-OUT-001');

        $inCollection->product->collections()->attach($collection->id);

        app(ProductSearchIndexer::class)->index($inCollection->product->fresh(['variants', 'categories']));
        app(ProductSearchIndexer::class)->index($outside->product->fresh(['variants', 'categories']));

        $this->get(route('storefront.catalog.collections.show', $collection->slug))
            ->assertOk()
            ->assertSee($inCollection->product->name)
            ->assertDontSee($outside->product->name);
    }

    public function test_shop_filters_products_by_collection_query_param(): void
    {
        $collection = Collection::query()->create([
            'name' => 'Outlet',
            'slug' => 'outlet',
        ]);

        $inCollection = $this->createPurchasableProduct(price: 100, stock: 3, sku: 'COLL-SHOP-001');
        $outside = $this->createPurchasableProduct(price: 100, stock: 3, sku: 'COLL-SHOP-002');

        $inCollection->product->collections()->attach($collection->id);

        app(ProductSearchIndexer::class)->index($inCollection->product->fresh(['variants', 'categories']));
        app(ProductSearchIndexer::class)->index($outside->product->fresh(['variants', 'categories']));

        $this->get(route('storefront.shop.index', ['collection' => 'outlet']))
            ->assertOk()
            ->assertSee($inCollection->product->name)
            ->assertDontSee($outside->product->name);
    }
}
