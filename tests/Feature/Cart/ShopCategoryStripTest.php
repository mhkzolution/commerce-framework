<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use Commerce\Catalog\Models\Category;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShopCategoryStripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_shop_shows_parent_categories_at_the_top_not_in_the_filter_bar(): void
    {
        [$parent, $child] = $this->apparelTree();
        $this->publishedProduct('Child Tee', 'STRIP-CHILD', [$child->id]);

        $html = $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->getContent();

        $strip = $this->stripHtml($html);

        $this->assertNotSame('', $strip);
        $this->assertStringContainsString('Apparel', $strip);
        $this->assertStringNotContainsString('Tops', $strip);
        $this->assertStringNotContainsString(__('storefront::storefront.shop_all_categories'), $strip);
        $this->assertStringNotContainsString('storefront-category-tree', $html);
        $this->assertStringNotContainsString(__('storefront::storefront.filter_category'), $html);
        $this->assertStringContainsString(
            'href="'.route('storefront.shop.index', ['category' => $parent->slug]).'"',
            $strip,
        );
    }

    public function test_parent_selection_shows_children_and_back_to_all_categories(): void
    {
        [$parent, $child] = $this->apparelTree();
        $this->publishedProduct('Child Tee', 'STRIP-PARENT', [$child->id]);

        $html = $this->get(route('storefront.shop.index', [
            'category' => $parent->slug,
            'brand' => 'acme-brand',
        ]))
            ->assertOk()
            ->getContent();

        $strip = $this->stripHtml($html);

        $this->assertStringContainsString(__('storefront::storefront.shop_all_categories'), $strip);
        $this->assertStringContainsString('Tops', $strip);
        $this->assertStringContainsString(
            'href="'.e(route('storefront.shop.index', ['brand' => 'acme-brand'])).'"',
            $strip,
        );
        $this->assertStringContainsString(
            'href="'.e(route('storefront.shop.index', [
                'category' => $child->slug,
                'brand' => 'acme-brand',
            ])).'"',
            $strip,
        );
        $this->assertStringContainsString('name="category"', $html);
        $this->assertStringContainsString('value="'.$parent->slug.'"', $html);
        $this->assertStringNotContainsString('storefront-category-tree', $html);
    }

    public function test_child_selection_keeps_sibling_chips_and_back(): void
    {
        [$parent, $child] = $this->apparelTree();
        $sibling = Category::query()->create([
            'name' => 'Bottoms',
            'slug' => 'bottoms',
            'parent_id' => $parent->id,
            'is_active' => true,
            'position' => 11,
        ]);
        $this->publishedProduct('Child Tee', 'STRIP-LEAF-1', [$child->id]);
        $this->publishedProduct('Child Shorts', 'STRIP-LEAF-2', [$sibling->id]);

        $html = $this->get(route('storefront.shop.index', ['category' => $child->slug]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('storefront::storefront.shop_all_categories'), $html);
        $this->assertStringContainsString('Tops', $html);
        $this->assertStringContainsString('Bottoms', $html);
        $this->assertStringContainsString('storefront-shop-category-strip__link--active', $html);
        $this->assertStringContainsString('value="'.$child->slug.'"', $html);
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

    private function stripHtml(string $html): string
    {
        if (preg_match('/<nav\b[^>]*storefront-shop-category-strip[\s\S]*?<\/nav>/', $html, $match) !== 1) {
            return '';
        }

        return $match[0];
    }
}
