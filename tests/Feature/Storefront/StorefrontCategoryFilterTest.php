<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Catalog\Models\Category;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontCategoryFilterTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_shop_page_hides_category_filter_in_sidebar(): void
    {
        $category = Category::query()->create([
            'name' => 'Outdoor',
            'slug' => 'outdoor',
            'is_active' => true,
        ]);

        $product = $this->createPurchasableProduct(price: 100, stock: 3, sku: 'CAT-FILTER-001');
        $product->product->categories()->attach($category->id);

        app(ProductSearchIndexer::class)->index($product->product->fresh(['variants', 'categories']));

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('Outdoor')
            ->assertDontSee('data-category-filter', false);
    }

    public function test_shop_filters_products_by_category_slug(): void
    {
        $category = Category::query()->create([
            'name' => 'Babies',
            'slug' => 'babies',
            'is_active' => true,
        ]);

        $inCategory = $this->createPurchasableProduct(price: 100, stock: 3, sku: 'CAT-SHOP-001');
        $outside = $this->createPurchasableProduct(price: 100, stock: 3, sku: 'CAT-SHOP-002');
        $inCategory->product->categories()->attach($category->id);

        app(ProductSearchIndexer::class)->index($inCategory->product->fresh(['variants', 'categories']));
        app(ProductSearchIndexer::class)->index($outside->product->fresh(['variants', 'categories']));

        $this->get(route('storefront.shop.index', ['category' => $category->slug]))
            ->assertOk()
            ->assertSee($inCategory->product->name)
            ->assertDontSee($outside->product->name);
    }
}
