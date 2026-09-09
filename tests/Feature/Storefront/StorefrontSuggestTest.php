<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Services\ProductDiscoveryQuery;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class StorefrontSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggest_http_never_calls_product_discovery_query(): void
    {
        $this->app->bind(
            ProductDiscoveryQuery::class,
            static fn (): never => throw new \LogicException('Suggest must not resolve product discovery.'),
        );
        $product = $this->product('Tee', 'HTTP-TEE-1');

        $this->getJson(route('storefront.suggest', ['q' => 'te']))
            ->assertOk()
            ->assertJsonMissingPath('data')
            ->assertJsonPath('completions.0.label', 'Tee')
            ->assertJsonPath('products.0.label', 'Tee')
            ->assertJsonPath('products.0.url', route('storefront.products.show', $product->slug))
            ->assertJsonPath('products.0.image_url', null);
    }

    public function test_suggest_http_returns_classic_tee_for_word_prefix(): void
    {
        $product = $this->product('Classic Tee', 'HTTP-CLASSIC-TEE');

        $this->getJson(route('storefront.suggest', ['q' => 'te']))
            ->assertOk()
            ->assertJsonFragment([
                'label' => 'Classic Tee',
                'url' => route('storefront.products.show', $product->slug),
            ])
            ->assertJsonFragment([
                'label' => 'Classic Tee',
                'url' => route('storefront.shop.index', ['q' => 'Classic Tee']),
            ]);
    }

    public function test_short_q_is_empty_json_and_skips_search_documents(): void
    {
        $this->product('Tee', 'HTTP-TEE-2');
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson(route('storefront.suggest', ['q' => 't']))
            ->assertOk()
            ->assertExactJson($this->emptyPayload());

        $this->assertFalse(collect(DB::getQueryLog())->contains(
            static fn (array $entry): bool => str_contains($entry['query'], 'search_documents'),
        ));
    }

    public function test_missing_q_is_empty_json(): void
    {
        $this->getJson(route('storefront.suggest'))
            ->assertOk()
            ->assertExactJson($this->emptyPayload());
    }

    public function test_shop_listing_q_cot_still_uses_phase_2_exact_token(): void
    {
        $cotton = $this->product('Cotton Parka', 'HTTP-COTTON-1');

        $this->get(route('storefront.shop.index', ['q' => 'cot']))
            ->assertOk()
            ->assertDontSee($cotton->name);
    }

    public function test_suggest_http_product_includes_image_url(): void
    {
        $product = $this->product('Tee', 'HTTP-TEE-IMG');
        $mediaUuid = 'media-http-tee';
        ProductMedia::query()->create([
            'product_id' => $product->id,
            'media_uuid' => $mediaUuid,
            'position' => 0,
            'is_primary' => true,
        ]);

        $this->app->instance(MediaQueryServiceInterface::class, new class($mediaUuid) implements MediaQueryServiceInterface
        {
            public function __construct(private readonly string $uuid) {}

            public function findByUuid(string $uuid): ?object
            {
                return null;
            }

            public function getUrl(string $uuid, ?string $variant = null): ?string
            {
                return $uuid === $this->uuid ? 'https://cdn.example.test/http-tee.jpg' : null;
            }

            public function getSrcset(string $uuid): ?string
            {
                return null;
            }

            public function findByUuids(array $uuids): array
            {
                return [];
            }
        });

        $this->getJson(route('storefront.suggest', ['q' => 'te']))
            ->assertOk()
            ->assertJsonPath('products.0.image_url', 'https://cdn.example.test/http-tee.jpg')
            ->assertJsonMissingPath('brands.0.image_url')
            ->assertJsonMissingPath('categories.0.image_url');
    }

    /**
     * @return array{
     *     completions: array<never>,
     *     products: array<never>,
     *     brands: array<never>,
     *     categories: array<never>
     * }
     */
    private function emptyPayload(): array
    {
        return [
            'completions' => [],
            'products' => [],
            'brands' => [],
            'categories' => [],
        ];
    }

    private function product(string $name, string $sku): Product
    {
        $product = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: $name,
            status: 'published',
            visibility: 'public',
            sku: $sku,
            price: 2500,
        ));

        $variant = $product->defaultVariant();
        $this->assertNotNull($variant);
        app(InventoryServiceInterface::class)->receive($variant->uuid, 5);
        app(ProductSearchIndexer::class)->index($product->fresh());

        return $product->fresh();
    }
}
