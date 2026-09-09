<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use Commerce\Cart\DTO\ShopFilterCatalog;
use Commerce\Cart\DTO\ShopListingFilters;
use Commerce\Cart\Services\HomepageNavigationQuery;
use Commerce\Cart\Services\HomepageProductQuery;
use Commerce\Cart\Services\ShopProductQuery;
use Commerce\Cart\Services\StorefrontPrimaryNavigation;
use Commerce\Catalog\Models\Category;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CategoryNavigationV1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_parent_shop_listing_includes_descendant_products(): void
    {
        [$parent, $child] = $this->apparelTree();
        $this->publishedProduct('Child Tee', 'NAV-CHILD', [$child->id]);
        $this->publishedProduct('Parent Only', 'NAV-PARENT', [$parent->id]);
        $this->publishedProduct('Other', 'NAV-OTHER', []);

        $paginator = app(ShopProductQuery::class)->paginate(
            new ShopListingFilters(category: 'apparel'),
            new ShopFilterCatalog,
        );

        $names = $paginator->getCollection()->pluck('name')->all();

        $this->assertContains('Child Tee', $names);
        $this->assertContains('Parent Only', $names);
        $this->assertNotContains('Other', $names);
    }

    public function test_child_shop_listing_excludes_parent_only_products(): void
    {
        [$parent, $child] = $this->apparelTree();
        $this->publishedProduct('Child Tee', 'NAV-CHILD-2', [$child->id]);
        $this->publishedProduct('Parent Only', 'NAV-PARENT-2', [$parent->id]);

        $paginator = app(ShopProductQuery::class)->paginate(
            new ShopListingFilters(category: 'tops'),
            new ShopFilterCatalog,
        );

        $names = $paginator->getCollection()->pluck('name')->all();

        $this->assertSame(['Child Tee'], $names);
    }

    public function test_empty_categories_are_omitted_and_parent_counts_include_descendants(): void
    {
        [$parent, $child] = $this->apparelTree();
        Category::query()->create([
            'name' => 'Empty Leaf',
            'slug' => 'empty-leaf',
            'parent_id' => $parent->id,
            'is_active' => true,
            'position' => 20,
        ]);
        Category::query()->create([
            'name' => 'Empty Root',
            'slug' => 'empty-root',
            'is_active' => true,
            'position' => 2,
        ]);
        $this->publishedProduct('Child Tee', 'NAV-COUNT', [$child->id]);

        $featured = app(HomepageNavigationQuery::class)->featured();
        $slugs = array_map(static fn ($item): string => $item->slug, $featured);

        $this->assertSame(['apparel'], $slugs);
        $this->assertSame(1, $featured[0]->productCount);

        $tree = app(HomepageNavigationQuery::class)->shopFilterOptions();
        $this->assertCount(1, $tree);
        $this->assertSame('apparel', $tree[0]->slug);
        $this->assertCount(1, $tree[0]->children);
        $this->assertSame('tops', $tree[0]->children[0]->slug);
        $this->assertSame(1, $tree[0]->productCount);
        $this->assertSame(1, $tree[0]->children[0]->productCount);
    }

    public function test_homepage_arrivals_on_parent_include_descendant_products(): void
    {
        [$parent, $child] = $this->apparelTree();
        $this->publishedProduct('Child Tee', 'NAV-ARRIVAL', [$child->id]);

        $cards = app(HomepageProductQuery::class)->arrivals('apparel');

        $this->assertSame(['Child Tee'], array_map(static fn ($card) => $card->name, $cards));
        $this->assertSame($parent->slug, 'apparel');
    }

    public function test_mega_menu_and_shop_page_render_category_tree_with_active_parent(): void
    {
        [$parent, $child] = $this->apparelTree();
        $this->publishedProduct('Child Tee', 'NAV-HTML', [$child->id]);

        $nav = app(StorefrontPrimaryNavigation::class)->build();
        $shop = collect($nav['items'])->firstWhere('id', 'shop');
        $this->assertIsArray($shop);
        $categoriesColumn = $shop['columns'][0] ?? [];
        $groups = $categoriesColumn['groups'] ?? [];
        $this->assertNotEmpty($groups);
        $this->assertSame('Apparel', $groups[0]['label']);
        $this->assertSame('Tops', $groups[0]['children'][0]['label']);

        $html = $this->get(route('storefront.shop.index', ['category' => 'tops']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storefront-mega-menu__group', $html);
        $this->assertStringContainsString('Apparel', $html);
        $this->assertStringContainsString('Tops', $html);
        $this->assertStringContainsString('storefront-shop-category-strip', $html);
        $this->assertStringNotContainsString('storefront-category-tree', $html);
        $this->assertStringContainsString('storefront-mega-menu__heading--active', $html);
        $this->assertStringContainsString('storefront-shop-category-strip__link--active', $html);
        $this->assertStringContainsString('data-mobile-nav-panel="shop"', $html);
        $this->assertStringNotContainsString('Empty Leaf', $html);
    }

    /**
     * @return array{0: Category, 1: Category}
     */
    private function apparelTree(): array
    {
        $parent = Category::query()->create([
            'name' => 'Apparel',
            'slug' => 'apparel',
            'is_active' => true,
            'position' => 1,
        ]);
        $child = Category::query()->create([
            'name' => 'Tops',
            'slug' => 'tops',
            'parent_id' => $parent->id,
            'is_active' => true,
            'position' => 10,
        ]);

        return [$parent, $child];
    }

    private function publishedProduct(string $name, string $sku, array $categoryIds): Product
    {
        $product = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: $name,
            status: 'published',
            visibility: 'public',
            sku: $sku,
            price: 1500,
            categoryIds: $categoryIds,
        ));

        $variant = $product->defaultVariant();
        $this->assertNotNull($variant);
        app(InventoryServiceInterface::class)->receive($variant->uuid, 4);

        return $product;
    }
}
