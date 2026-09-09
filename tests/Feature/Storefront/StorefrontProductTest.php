<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Catalog\Models\Category;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontProductTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_product_detail_page_is_accessible(): void
    {
        $variant = $this->createPurchasableProduct(price: 3200, stock: 3, sku: 'DETAIL-001');
        $product = $variant->product;

        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $this->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee(__('storefront::storefront.add_to_cart'));
    }

    public function test_pdp_share_menu_includes_facebook_line_x_and_copy(): void
    {
        $variant = $this->createPurchasableProduct(price: 3200, stock: 3, sku: 'SHARE-001');
        $product = $variant->product;
        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $html = $this->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('facebook.com/sharer/sharer.php?u=', $html);
        $this->assertStringContainsString('social-plugins.line.me/lineit/share?url=', $html);
        $this->assertStringContainsString('twitter.com/intent/tweet?url=', $html);
        $this->assertStringContainsString('data-share-copy', $html);
    }

    public function test_unknown_product_returns_404(): void
    {
        $this->get('/products/does-not-exist')->assertNotFound();
    }

    public function test_child_pdp_renders_home_parent_child_trail_and_assigned_badge(): void
    {
        $parent = Category::query()->create([
            'name' => 'Apparel',
            'slug' => 'pdp-http-apparel',
            'is_active' => true,
            'position' => 1,
        ]);
        $child = Category::query()->create([
            'name' => 'Tops',
            'slug' => 'pdp-http-tops',
            'parent_id' => $parent->id,
            'is_active' => true,
            'position' => 10,
        ]);
        $product = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: 'HTTP Child Polo',
            status: 'published',
            visibility: 'public',
            sku: 'PDP-HTTP-CHILD',
            price: 1500,
            categoryIds: [$child->id],
        ));
        $variant = $product->defaultVariant();
        $this->assertNotNull($variant);
        app(InventoryServiceInterface::class)->receive($variant->uuid, 4);

        $html = $this->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->getContent();

        $crumbs = $this->breadcrumbLabels($html);
        $this->assertSame([
            __('storefront::storefront.home'),
            'Apparel',
            'Tops',
            'HTTP Child Polo',
        ], $crumbs);
        $this->assertStringContainsString('storefront-buy-box__category', $html);
        $this->assertStringContainsString('shop?category=pdp-http-tops', $html);
        $this->assertNotContains(__('storefront::storefront.shop'), $crumbs);
    }

    public function test_uncategorized_pdp_omits_category_badge(): void
    {
        $product = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: 'HTTP No Category',
            status: 'published',
            visibility: 'public',
            sku: 'PDP-HTTP-NONE',
            price: 1500,
        ));
        $variant = $product->defaultVariant();
        $this->assertNotNull($variant);
        app(InventoryServiceInterface::class)->receive($variant->uuid, 4);

        $html = $this->get(route('storefront.products.show', $product->slug))
            ->assertOk()
            ->getContent();

        $this->assertSame([
            __('storefront::storefront.home'),
            'HTTP No Category',
        ], $this->breadcrumbLabels($html));
        $this->assertStringNotContainsString('storefront-buy-box__category', $html);
    }

    /**
     * @return list<string>
     */
    private function breadcrumbLabels(string $html): array
    {
        $document = new \DOMDocument;
        $this->assertTrue(@$document->loadHTML($html));
        $xpath = new \DOMXPath($document);
        $nodes = $xpath->query('//nav[contains(@class,"storefront-breadcrumb")]//li');
        $this->assertNotFalse($nodes);

        $labels = [];
        foreach ($nodes as $node) {
            $labels[] = trim($node->textContent);
        }

        return $labels;
    }

    public function test_shop_search_finds_indexed_product(): void
    {
        $variant = $this->createPurchasableProduct(price: 1500, stock: 2, sku: 'SHOP-SEARCH-99');
        $product = $variant->product;

        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $this->get(route('storefront.shop.index', ['search' => 'SHOP-SEARCH-99']))
            ->assertOk()
            ->assertSee($product->name);
    }
}
