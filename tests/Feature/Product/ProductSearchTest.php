<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\DTO\UpdateProductData;
use Commerce\Product\Services\ProductQueryService;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductSearchTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_product_search_index_and_query(): void
    {
        $variant = $this->createPurchasableProduct(price: 15, stock: 5, sku: 'SEARCH-SKU-001');
        $product = $variant->product;

        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $results = app(ProductQueryService::class)->paginate('SEARCH-SKU');

        $this->assertGreaterThanOrEqual(1, $results->total());
        $this->assertTrue(
            collect($results->items())->contains(static fn ($item) => $item->uuid === $product->uuid),
        );
    }

    public function test_admin_product_search_pagination_preserves_admin_path(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $this->createPurchasableProduct(price: 10, stock: 1, sku: sprintf('SKU-200-%02d', $i));
        }

        $user = User::query()->firstOrFail();

        $firstPage = $this->actingAs($user)
            ->get(route('admin.products.index', ['search' => '200']))
            ->assertOk();

        $firstPage->assertSee('page=2', false);
        $firstPage->assertDontSee('href="/shop"', false);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['search' => '200', 'page' => 2]))
            ->assertOk()
            ->assertSee('SKU-200-26', false);
    }

    public function test_product_slug_redirect_on_update(): void
    {
        $product = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: 'Slug Test Product',
            slug: 'slug-test-product',
            status: 'published',
            visibility: 'public',
            sku: 'SLUG-001',
            price: 10,
        ));

        app(ProductServiceInterface::class)->update($product->uuid, new UpdateProductData(
            name: 'Slug Test Product Renamed',
            slug: 'slug-test-renamed',
            description: $product->description,
            status: 'published',
            visibility: 'public',
            sku: 'SLUG-001',
            price: 10,
        ));

        $redirect = app(UrlRedirectServiceInterface::class)
            ->resolve('/products/slug-test-product');

        $this->assertSame('/products/slug-test-renamed', $redirect);
    }
}
