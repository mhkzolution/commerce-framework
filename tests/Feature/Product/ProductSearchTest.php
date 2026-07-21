<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\Services\ProductQueryService;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductSearchTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_product_search_index_and_query(): void
    {
        $variant = $this->createPurchasableProduct(price: 1500, stock: 5, sku: 'SEARCH-SKU-001');
        $product = $variant->product;

        app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));

        $results = app(ProductQueryService::class)->paginate('SEARCH-SKU');

        $this->assertGreaterThanOrEqual(1, $results->total());
        $this->assertTrue(
            collect($results->items())->contains(static fn ($item) => $item->uuid === $product->uuid),
        );
    }

    public function test_product_slug_redirect_on_update(): void
    {
        $product = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: 'Slug Test Product',
            slug: 'slug-test-product',
            status: 'published',
            visibility: 'public',
            sku: 'SLUG-001',
            price: 1000,
        ));

        app(ProductServiceInterface::class)->update($product->uuid, new \Commerce\Product\DTO\UpdateProductData(
            name: 'Slug Test Product Renamed',
            slug: 'slug-test-renamed',
            description: $product->description,
            type: $product->type,
            status: 'published',
            visibility: 'public',
            sku: 'SLUG-001',
            price: 1000,
        ));

        $redirect = app(\Commerce\Contracts\Seo\UrlRedirectServiceInterface::class)
            ->resolve('/products/slug-test-product');

        $this->assertSame('/products/slug-test-renamed', $redirect);
    }
}
