<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Catalog\Contracts\AttributeServiceInterface;
use Commerce\Catalog\DTO\CreateAttributeData;
use Commerce\Catalog\DTO\CreateBrandData;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Services\BrandService;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontShopFilterChromeTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_header_uses_old_chrome_with_primary_nav_and_search_overlay(): void
    {
        $html = $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storefront-header', $html);
        $this->assertStringContainsString('storefront-primary-nav', $html);
        $this->assertStringContainsString('data-search-open', $html);
        $this->assertStringContainsString('storefront-search-overlay', $html);
        $this->assertStringContainsString('storefront-header-actions', $html);
        $this->assertStringContainsString('data-drawer-open="wishlist"', $html);
        $this->assertStringContainsString('data-drawer-open="cart"', $html);
        $this->assertStringContainsString('data-drawer="wishlist"', $html);
        $this->assertStringContainsString('data-drawer="cart"', $html);
        $this->assertStringContainsString('data-wishlist-root', $html);
        $this->assertStringContainsString(__('storefront::storefront.nav_shop'), $html);
        $this->assertStringContainsString(__('storefront::storefront.nav_new_in'), $html);
        $this->assertStringNotContainsString('x-site.logo', $html);
    }

    public function test_shop_filters_by_price_preset_and_size(): void
    {
        $sizeAttribute = app(AttributeServiceInterface::class)->create(new CreateAttributeData(
            code: 'size',
            name: 'Size',
            type: 'select',
            isFilterable: true,
            options: ['S', 'M', 'L'],
        ));

        $matching = $this->createProductWithAttributes('Matching Product', 75000, ['S'], $sizeAttribute->id);
        $tooCheap = $this->createProductWithAttributes('Too Cheap', 30000, ['S'], $sizeAttribute->id);
        $wrongSize = $this->createProductWithAttributes('Wrong Size', 75000, ['L'], $sizeAttribute->id);

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

    public function test_shop_renders_filter_sidebar_and_keeps_availability(): void
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

        $product = $this->createPurchasableProduct(price: 10000, stock: 2, sku: 'FILTER-UI-1')->product;
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

        $html = $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee(__('storefront::storefront.filter_size'))
            ->assertSee(__('storefront::storefront.filter_color'))
            ->assertSee('0 – 500')
            ->assertSee('Acme Brand')
            ->assertSee('M')
            ->getContent();

        $this->assertStringContainsString('storefront-shop-filters-sidebar', $html);
        $this->assertStringContainsString('storefront-filters', $html);
        $this->assertStringContainsString('data-filters-sheet', $html);
        $this->assertStringContainsString('storefront-product-grid', $html);
        $this->assertStringContainsString('name="availability"', $html);
        $this->assertStringContainsString('storefront-primary-nav', $html);
        $this->assertStringNotContainsString(__('storefront::storefront.filter_brand_search'), $html);
        $this->assertStringNotContainsString('>Language<', $html);
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

        $product = $this->createPurchasableProduct(price: 50000, stock: 3, sku: 'COLOR-JSON-1')->product;
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
            ->assertSee($product->name);
    }

    public function test_shop_shows_breadcrumb_for_search_and_hides_it_without_filters(): void
    {
        $this->get(route('storefront.shop.index', ['search' => 'nike shoes']))
            ->assertOk()
            ->assertSee('storefront-breadcrumb', false)
            ->assertSee('nike shoes', false);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertDontSee('storefront-breadcrumb', false);
    }

    public function test_header_search_is_prefilled_from_query_string(): void
    {
        $html = $this->get(route('storefront.shop.index', ['search' => 'harbor mug']))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/name="search"[^>]*value="harbor mug"|value="harbor mug"[^>]*name="search"/',
            $html,
        );
    }

    public function test_shop_filters_by_brand_slug(): void
    {
        $acme = app(BrandService::class)->create(new CreateBrandData(
            name: 'Acme Brand',
            slug: 'acme-brand',
            isActive: true,
        ));
        $other = app(BrandService::class)->create(new CreateBrandData(
            name: 'Other Brand',
            slug: 'other-brand',
            isActive: true,
        ));

        $acmeProduct = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: 'Acme Mug',
            status: 'published',
            visibility: 'public',
            sku: 'ACME-MUG-1',
            price: 2100,
            brandUuid: $acme->uuid,
        ));
        $otherProduct = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: 'Other Mug',
            status: 'published',
            visibility: 'public',
            sku: 'OTHER-MUG-1',
            price: 2100,
            brandUuid: $other->uuid,
        ));

        $acmeVariant = $acmeProduct->defaultVariant();
        $otherVariant = $otherProduct->defaultVariant();
        $this->assertNotNull($acmeVariant);
        $this->assertNotNull($otherVariant);
        app(InventoryServiceInterface::class)->receive($acmeVariant->uuid, 2);
        app(InventoryServiceInterface::class)->receive($otherVariant->uuid, 2);

        $this->get(route('storefront.shop.index', ['brand' => 'acme-brand']))
            ->assertOk()
            ->assertSee('Acme Mug')
            ->assertDontSee('Other Mug');
    }

    /**
     * @param  list<string>  $values
     */
    private function createProductWithAttributes(string $name, int $price, array $values, int $attributeId): Product
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
        $this->assertNotNull($variant);
        app(InventoryServiceInterface::class)->receive($variant->uuid, 5);
        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        return $product;
    }
}
