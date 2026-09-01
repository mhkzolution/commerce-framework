<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Catalog\DTO\CreateCollectionData;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Collection;
use Commerce\Catalog\Models\Tag;
use Commerce\Catalog\Services\CollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class AutomatedCollectionTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_automated_on_sale_collection_syncs_matching_products(): void
    {
        $onSale = $this->createPurchasableProduct(price: 80, stock: 2, sku: 'AUTO-SALE-001');
        $onSale->update(['compare_at_price' => 120]);

        $regular = $this->createPurchasableProduct(price: 80, stock: 2, sku: 'AUTO-SALE-002');

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'On Sale',
            slug: 'on-sale',
            type: Collection::TYPE_AUTOMATED,
            rules: ['on_sale' => true],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertContains($onSale->product_id, $productIds);
        $this->assertNotContains($regular->product_id, $productIds);
    }

    public function test_automated_category_collection_syncs_products_in_category(): void
    {
        $category = Category::query()->create([
            'name' => 'Toys',
            'slug' => 'toys',
            'is_active' => true,
        ]);

        $inCategory = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-CAT-001');
        $outside = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-CAT-002');
        $inCategory->product->categories()->attach($category->id);

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'Toys Collection',
            slug: 'toys-collection',
            type: Collection::TYPE_AUTOMATED,
            rules: ['category_id' => $category->id],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertContains($inCategory->product_id, $productIds);
        $this->assertNotContains($outside->product_id, $productIds);
    }

    public function test_automated_brand_collection_syncs_matching_products(): void
    {
        $brand = Brand::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $inBrand = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-BRAND-001');
        $outside = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-BRAND-002');
        $inBrand->product->update(['brand_uuid' => $brand->uuid]);

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'Acme Collection',
            slug: 'acme-collection',
            type: Collection::TYPE_AUTOMATED,
            rules: ['brand_uuid' => $brand->uuid],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertContains($inBrand->product_id, $productIds);
        $this->assertNotContains($outside->product_id, $productIds);
    }

    public function test_automated_tag_collection_syncs_matching_products(): void
    {
        $tag = Tag::query()->create([
            'name' => 'Summer',
            'slug' => 'summer',
        ]);

        $tagged = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-TAG-001');
        $outside = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-TAG-002');
        $tagged->product->tags()->attach($tag->id);

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'Summer Collection',
            slug: 'summer-collection',
            type: Collection::TYPE_AUTOMATED,
            rules: ['tag_id' => $tag->id],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertContains($tagged->product_id, $productIds);
        $this->assertNotContains($outside->product_id, $productIds);
    }

    public function test_automated_price_range_collection_syncs_matching_products(): void
    {
        $cheap = $this->createPurchasableProduct(price: 30, stock: 2, sku: 'AUTO-PRICE-001');
        $mid = $this->createPurchasableProduct(price: 75, stock: 2, sku: 'AUTO-PRICE-002');
        $expensive = $this->createPurchasableProduct(price: 150, stock: 2, sku: 'AUTO-PRICE-003');

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'Mid Range',
            slug: 'mid-range',
            type: Collection::TYPE_AUTOMATED,
            rules: ['price_min' => 50, 'price_max' => 100],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertNotContains($cheap->product_id, $productIds);
        $this->assertContains($mid->product_id, $productIds);
        $this->assertNotContains($expensive->product_id, $productIds);
    }

    public function test_sync_automated_collections_command_runs_successfully(): void
    {
        app(CollectionService::class)->create(new CreateCollectionData(
            name: 'Command Test',
            slug: 'command-test',
            type: Collection::TYPE_AUTOMATED,
            rules: ['on_sale' => true],
        ));

        $this->artisan('catalog:sync-automated-collections')
            ->assertSuccessful();
    }

    public function test_automated_collection_with_any_match_syncs_products_matching_either_rule(): void
    {
        $category = Category::query()->create([
            'name' => 'Gadgets',
            'slug' => 'gadgets',
            'is_active' => true,
        ]);

        $onSale = $this->createPurchasableProduct(price: 80, stock: 2, sku: 'AUTO-ANY-001');
        $onSale->update(['compare_at_price' => 120]);

        $inCategory = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-ANY-002');
        $inCategory->product->categories()->attach($category->id);

        $outside = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-ANY-003');

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'Sale or Gadgets',
            slug: 'sale-or-gadgets',
            type: Collection::TYPE_AUTOMATED,
            rules: [
                'match' => 'any',
                'on_sale' => true,
                'category_id' => $category->id,
            ],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertContains($onSale->product_id, $productIds);
        $this->assertContains($inCategory->product_id, $productIds);
        $this->assertNotContains($outside->product_id, $productIds);
    }

    public function test_automated_collection_with_multiple_brands_syncs_matching_products(): void
    {
        $brandA = Brand::query()->create(['name' => 'Brand A', 'slug' => 'brand-a', 'is_active' => true]);
        $brandB = Brand::query()->create(['name' => 'Brand B', 'slug' => 'brand-b', 'is_active' => true]);

        $inA = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-MULTI-001');
        $inB = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-MULTI-002');
        $outside = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-MULTI-003');

        $inA->product->update(['brand_uuid' => $brandA->uuid]);
        $inB->product->update(['brand_uuid' => $brandB->uuid]);

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'Multi Brand',
            slug: 'multi-brand',
            type: Collection::TYPE_AUTOMATED,
            rules: ['brand_uuids' => [$brandA->uuid, $brandB->uuid]],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertContains($inA->product_id, $productIds);
        $this->assertContains($inB->product_id, $productIds);
        $this->assertNotContains($outside->product_id, $productIds);
    }

    public function test_automated_collection_with_multiple_categories_syncs_matching_products(): void
    {
        $categoryA = Category::query()->create(['name' => 'Shoes', 'slug' => 'shoes', 'is_active' => true]);
        $categoryB = Category::query()->create(['name' => 'Bags', 'slug' => 'bags', 'is_active' => true]);

        $inA = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-CAT-MULTI-001');
        $inB = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-CAT-MULTI-002');
        $outside = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-CAT-MULTI-003');

        $inA->product->categories()->attach($categoryA->id);
        $inB->product->categories()->attach($categoryB->id);

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'Shoes or Bags',
            slug: 'shoes-or-bags',
            type: Collection::TYPE_AUTOMATED,
            rules: ['category_ids' => [$categoryA->id, $categoryB->id]],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertContains($inA->product_id, $productIds);
        $this->assertContains($inB->product_id, $productIds);
        $this->assertNotContains($outside->product_id, $productIds);
    }

    public function test_automated_collection_with_all_categories_requires_every_category(): void
    {
        $shoes = Category::query()->create(['name' => 'Shoes', 'slug' => 'shoes-all', 'is_active' => true]);
        $kids = Category::query()->create(['name' => 'Kids', 'slug' => 'kids-all', 'is_active' => true]);

        $inBoth = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-CAT-ALL-001');
        $inShoesOnly = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-CAT-ALL-002');

        $inBoth->product->categories()->attach([$shoes->id, $kids->id]);
        $inShoesOnly->product->categories()->attach($shoes->id);

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'Shoes and Kids',
            slug: 'shoes-and-kids',
            type: Collection::TYPE_AUTOMATED,
            rules: [
                'category_ids' => [$shoes->id, $kids->id],
                'category_match' => 'all',
            ],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertContains($inBoth->product_id, $productIds);
        $this->assertNotContains($inShoesOnly->product_id, $productIds);
    }

    public function test_automated_collection_with_all_tags_requires_every_tag(): void
    {
        $summer = Tag::query()->create(['name' => 'Summer', 'slug' => 'summer-all']);
        $sale = Tag::query()->create(['name' => 'Sale', 'slug' => 'sale-all']);

        $withBoth = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-TAG-ALL-001');
        $withSummerOnly = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-TAG-ALL-002');

        $withBoth->product->tags()->attach([$summer->id, $sale->id]);
        $withSummerOnly->product->tags()->attach($summer->id);

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'Summer Sale',
            slug: 'summer-sale',
            type: Collection::TYPE_AUTOMATED,
            rules: [
                'tag_ids' => [$summer->id, $sale->id],
                'tag_match' => 'all',
            ],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertContains($withBoth->product_id, $productIds);
        $this->assertNotContains($withSummerOnly->product_id, $productIds);
    }

    public function test_automated_collection_with_all_brands_requires_single_brand_when_multiple_selected(): void
    {
        $brandA = Brand::query()->create(['name' => 'Brand All A', 'slug' => 'brand-all-a', 'is_active' => true]);
        $brandB = Brand::query()->create(['name' => 'Brand All B', 'slug' => 'brand-all-b', 'is_active' => true]);

        $inA = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-BRAND-ALL-001');
        $inB = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-BRAND-ALL-002');

        $inA->product->update(['brand_uuid' => $brandA->uuid]);
        $inB->product->update(['brand_uuid' => $brandB->uuid]);

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'All Brands Impossible',
            slug: 'all-brands-impossible',
            type: Collection::TYPE_AUTOMATED,
            rules: [
                'brand_uuids' => [$brandA->uuid, $brandB->uuid],
                'brand_match' => 'all',
            ],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertSame([], $productIds);
    }

    public function test_automated_collection_with_nested_groups_matches_intersection(): void
    {
        $category = Category::query()->create(['name' => 'Electronics', 'slug' => 'electronics-group', 'is_active' => true]);
        $brand = Brand::query()->create(['name' => 'TechCo', 'slug' => 'techco-group', 'is_active' => true]);

        $matching = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-GROUP-001');
        $categoryOnly = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-GROUP-002');
        $brandOnly = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-GROUP-003');

        $matching->product->categories()->attach($category->id);
        $matching->product->update(['brand_uuid' => $brand->uuid]);
        $categoryOnly->product->categories()->attach($category->id);
        $brandOnly->product->update(['brand_uuid' => $brand->uuid]);

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'Electronics TechCo',
            slug: 'electronics-techco',
            type: Collection::TYPE_AUTOMATED,
            rules: [
                'match' => 'all',
                'groups' => [
                    ['match' => 'all', 'category_ids' => [$category->id]],
                    ['match' => 'all', 'brand_uuids' => [$brand->uuid]],
                ],
            ],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertContains($matching->product_id, $productIds);
        $this->assertNotContains($categoryOnly->product_id, $productIds);
        $this->assertNotContains($brandOnly->product_id, $productIds);
    }

    public function test_automated_collection_with_nested_groups_matches_union(): void
    {
        $category = Category::query()->create(['name' => 'Books', 'slug' => 'books-group', 'is_active' => true]);

        $onSale = $this->createPurchasableProduct(price: 80, stock: 2, sku: 'AUTO-GROUP-OR-001');
        $onSale->update(['compare_at_price' => 120]);

        $inCategory = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-GROUP-OR-002');
        $inCategory->product->categories()->attach($category->id);

        $outside = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'AUTO-GROUP-OR-003');

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'Sale or Books',
            slug: 'sale-or-books-group',
            type: Collection::TYPE_AUTOMATED,
            rules: [
                'match' => 'any',
                'groups' => [
                    ['match' => 'all', 'on_sale' => true],
                    ['match' => 'all', 'category_ids' => [$category->id]],
                ],
            ],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertContains($onSale->product_id, $productIds);
        $this->assertContains($inCategory->product_id, $productIds);
        $this->assertNotContains($outside->product_id, $productIds);
    }

    public function test_automated_collection_with_three_nested_groups_matches_intersection(): void
    {
        $category = Category::query()->create(['name' => 'Phones', 'slug' => 'phones-group', 'is_active' => true]);
        $tag = Tag::query()->create(['name' => 'Featured', 'slug' => 'featured-group']);

        $matching = $this->createPurchasableProduct(price: 80, stock: 2, sku: 'AUTO-3GROUP-001');
        $matching->update(['compare_at_price' => 120]);
        $matching->product->categories()->attach($category->id);
        $matching->product->tags()->attach($tag->id);

        $onSaleOnly = $this->createPurchasableProduct(price: 80, stock: 2, sku: 'AUTO-3GROUP-002');
        $onSaleOnly->update(['compare_at_price' => 120]);

        $collection = app(CollectionService::class)->create(new CreateCollectionData(
            name: 'Featured sale phones',
            slug: 'featured-sale-phones',
            type: Collection::TYPE_AUTOMATED,
            rules: [
                'match' => 'all',
                'groups' => [
                    ['match' => 'all', 'on_sale' => true],
                    ['match' => 'all', 'category_ids' => [$category->id]],
                    ['match' => 'all', 'tag_ids' => [$tag->id]],
                ],
            ],
        ));

        $productIds = $collection->products()->pluck('products.id')->all();

        $this->assertContains($matching->product_id, $productIds);
        $this->assertNotContains($onSaleOnly->product_id, $productIds);
    }
}
