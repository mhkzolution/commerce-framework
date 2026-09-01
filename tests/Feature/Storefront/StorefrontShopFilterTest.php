<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Cart\Services\StorefrontNavigationCatalog;
use Commerce\Catalog\Contracts\AttributeServiceInterface;
use Commerce\Catalog\DTO\CreateAttributeData;
use Commerce\Catalog\DTO\CreateBrandData;
use Commerce\Catalog\DTO\CreateCategoryData;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Services\BrandService;
use Commerce\Catalog\Services\CategoryService;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontShopFilterTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_shop_hides_out_of_stock_products(): void
    {
        $inStock = $this->createPurchasableProduct(price: 100, stock: 5, sku: 'IN-STOCK-1');
        $outOfStock = $this->createPurchasableProduct(price: 100, stock: 3, sku: 'OUT-STOCK-1');

        app(InventoryServiceInterface::class)->adjust($outOfStock->uuid, -3);

        app(ProductSearchIndexer::class)->index($inStock->product->fresh(['variants', 'categories']));
        app(ProductSearchIndexer::class)->index($outOfStock->product->fresh(['variants', 'categories']));

        $response = $this->get(route('storefront.shop.index'));

        $response->assertOk()
            ->assertSee($inStock->product->name)
            ->assertDontSee($outOfStock->product->name);
    }

    public function test_shop_navigation_shows_all_active_categories(): void
    {
        app(CategoryService::class)->create(new CreateCategoryData(
            name: 'Has Products',
            slug: 'has-products',
            isActive: true,
        ));
        app(CategoryService::class)->create(new CreateCategoryData(
            name: 'Empty Category',
            slug: 'empty-category',
            isActive: true,
        ));

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('Has Products')
            ->assertSee('Empty Category');
    }

    public function test_navigation_catalog_cache_hydrates_models(): void
    {
        app(CategoryService::class)->create(new CreateCategoryData(
            name: 'Cached Category',
            slug: 'cached-category',
            isActive: true,
        ));

        Cache::flush();

        $catalog = app(StorefrontNavigationCatalog::class);
        $first = $catalog->categories();
        $second = app(StorefrontNavigationCatalog::class)->categories();

        $this->assertInstanceOf(Category::class, $first->first());
        $this->assertSame('cached-category', $second->firstWhere('slug', 'cached-category')?->slug);
    }

    public function test_header_uses_primary_navigation_instead_of_sliders(): void
    {
        $category = app(CategoryService::class)->create(new CreateCategoryData(
            name: 'Visible Category',
            slug: 'visible-category',
            isActive: true,
        ));

        $variant = $this->createPurchasableProduct(price: 100, stock: 5, sku: 'HEADER-NAV-1');
        $variant->product->categories()->attach($category->id);

        $response = $this->get(route('storefront.shop.index'));

        $response->assertOk()
            ->assertSee('storefront-primary-nav', false)
            ->assertSee('Visible Category')
            ->assertDontSee('storefront-category-slider', false)
            ->assertDontSee('storefront-collection-slider', false)
            ->assertDontSee('storefront-brand-slider', false);
    }

    public function test_shop_shows_breadcrumb_for_search_query(): void
    {
        $this->get(route('storefront.shop.index', ['search' => 'nike shoes']))
            ->assertOk()
            ->assertSee('storefront-breadcrumb', false)
            ->assertSee('nike shoes', false);
    }

    public function test_shop_hides_breadcrumb_without_filters(): void
    {
        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertDontSee('storefront-breadcrumb', false);
    }

    public function test_catalog_landing_shows_breadcrumb(): void
    {
        $category = app(CategoryService::class)->create(new CreateCategoryData(
            name: 'Landing Category',
            slug: 'landing-category',
            isActive: true,
        ));

        $variant = $this->createPurchasableProduct(price: 100, stock: 5, sku: 'LANDING-1');
        $variant->product->categories()->attach($category->id);

        $this->get(route('storefront.catalog.categories.show', $category->slug))
            ->assertOk()
            ->assertSee('storefront-breadcrumb', false)
            ->assertSee('Landing Category', false);
    }

    public function test_shop_filters_by_price_preset_and_size_badge(): void
    {
        $sizeAttribute = app(AttributeServiceInterface::class)->create(new CreateAttributeData(
            code: 'size',
            name: 'Size',
            type: 'select',
            isFilterable: true,
            options: ['S', 'M', 'L'],
        ));

        $matching = $this->createProductWithAttributes('Matching Product', 750, ['S'], $sizeAttribute->id);
        $tooCheap = $this->createProductWithAttributes('Too Cheap', 300, ['S'], $sizeAttribute->id);
        $wrongSize = $this->createProductWithAttributes('Wrong Size', 750, ['L'], $sizeAttribute->id);

        $this->get(route('storefront.shop.index', [
            'price_min' => 500,
            'price_max' => 1000,
            'size' => 'S',
        ]))
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee($tooCheap->name)
            ->assertDontSee($wrongSize->name);
    }

    public function test_shop_renders_new_filter_groups_and_hides_removed_filters(): void
    {
        $sizeAttribute = app(AttributeServiceInterface::class)->create(new CreateAttributeData(
            code: 'size',
            name: 'Size',
            type: 'select',
            isFilterable: true,
            options: ['M'],
        ));

        app(AttributeServiceInterface::class)->create(new CreateAttributeData(
            code: 'color',
            name: 'Color',
            type: 'select',
            isFilterable: true,
            options: ['Red'],
        ));

        $colorAttribute = Attribute::query()->where('code', 'color')->firstOrFail();

        app(AttributeServiceInterface::class)->create(new CreateAttributeData(
            code: 'language',
            name: 'Language',
            type: 'select',
            isFilterable: true,
            options: ['Thai'],
        ));

        $brand = app(BrandService::class)->create(new CreateBrandData(
            name: 'Acme Brand',
            slug: 'acme-brand',
            isActive: true,
        ));

        $product = $this->createPurchasableProduct(price: 100, stock: 2, sku: 'FILTER-UI-1')->product;
        $product->update([
            'brand_uuid' => $brand->uuid,
        ]);
        $product->attributeValues()->create([
            'attribute_id' => $sizeAttribute->id,
            'value' => 'M',
        ]);
        $product->attributeValues()->create([
            'attribute_id' => $colorAttribute->id,
            'value' => 'Red',
        ]);

        $response = $this->get(route('storefront.shop.index'));

        $response->assertOk()
            ->assertSee(__('storefront::storefront.filter_size'))
            ->assertSee(__('storefront::storefront.filter_color'))
            ->assertSee('0 – 500')
            ->assertSee('Acme Brand')
            ->assertSee('M')
            ->assertDontSee('name="availability"', false)
            ->assertDontSee('name="tags[]"', false)
            ->assertDontSee('>Language<', false)
            ->assertDontSee(__('storefront::storefront.filter_brand_search'));
    }

    public function test_shop_color_filter_displays_multiselect_json_values(): void
    {
        $colorAttribute = app(AttributeServiceInterface::class)->create(new CreateAttributeData(
            code: 'color',
            name: 'Color',
            type: 'multiselect',
            isFilterable: true,
            options: ['เหลือง', 'แดง'],
        ));

        $product = $this->createPurchasableProduct(price: 500, stock: 3, sku: 'COLOR-JSON-1')->product;
        $product->attributeValues()->create([
            'attribute_id' => $colorAttribute->id,
            'value' => json_encode(['เหลือง'], JSON_UNESCAPED_UNICODE),
        ]);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('เหลือง', false)
            ->assertDontSee('["เหลือง"]', false);

        $this->get(route('storefront.shop.index', ['color' => 'เหลือง']))
            ->assertOk()
            ->assertSee($product->name)
            ->assertDontSee('Wrong Size');
    }

    /**
     * @param  list<string>  $values
     */
    private function createProductWithAttributes(string $name, float $price, array $values, int $attributeId): Product
    {
        $product = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: $name,
            status: 'published',
            visibility: 'public',
            sku: strtoupper(substr(md5($name), 0, 8)),
            price: $price,
            attributeValues: [
                $attributeId => $values[0],
            ],
        ));

        $variant = $product->defaultVariant();
        app(InventoryServiceInterface::class)->receive($variant->uuid, 5);

        return $product;
    }
}
