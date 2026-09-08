<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Cart\DTO\ShopFilterCatalog;
use Commerce\Cart\DTO\ShopListingFilters;
use Commerce\Cart\Services\ShopProductQuery;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Contracts\Search\SearchQueryInterface;
use Commerce\Contracts\Search\SearchResultInterface;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\DTO\CreateVariantData;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ShopDiscoveryListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_empty_q_browses_without_reading_search_documents(): void
    {
        $listing = app(ShopProductQuery::class);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $listing->paginate(new ShopListingFilters(q: '  '), new ShopFilterCatalog);

        $this->assertFalse(collect(DB::getQueryLog())->contains(
            static fn (array $entry): bool => str_contains($entry['query'], 'search_documents'),
        ));
    }

    public function test_non_default_sku_exact_match_keeps_discovery_rank_in_latest_sort(): void
    {
        $exact = $this->product('Zebra Product', 'DEFAULT-SKU-1');
        $exact->update(['type' => 'variable']);
        app(ProductServiceInterface::class)->addVariant(new CreateVariantData(
            productUuid: $exact->uuid,
            sku: 'SKU-SPECIAL-002',
            name: 'Special',
            price: 2500,
            position: 1,
        ));
        $titleMatch = $this->product('SKU-SPECIAL-002', 'OTHER-SKU-1');
        $this->index($exact->fresh(), $titleMatch);
        $this->app->bind(SearchQueryInterface::class, static fn () => new class implements SearchQueryInterface
        {
            public function search(
                string $index,
                string $query,
                array $filters = [],
                int $page = 1,
                int $perPage = 25,
            ): SearchResultInterface {
                throw new \RuntimeException('Legacy database search must not be called.');
            }
        });

        $this->get(route('storefront.shop.index', ['q' => 'sku-special-002']))
            ->assertOk()
            ->assertSeeInOrder([$exact->name, $titleMatch->name]);
    }

    public function test_filterable_material_param_uses_non_axis_attribute_relation(): void
    {
        $material = $this->attribute('material');
        $cotton = $this->attributeValue($material, 'Cotton');
        $matching = $this->product('Cotton Tee', 'COTTON-TEE-1');
        $other = $this->product('Linen Tee', 'LINEN-TEE-1');

        ProductAttribute::query()->create([
            'product_id' => $matching->id,
            'attribute_id' => $material->id,
            'used_for_variations' => false,
            'position' => 0,
        ]);
        ProductAttributeValue::query()->create([
            'product_id' => $matching->id,
            'attribute_id' => $material->id,
            'product_variant_id' => null,
            'attribute_value_id' => $cotton->id,
            'value' => $cotton->label,
        ]);

        $this->get(route('storefront.shop.index', ['material' => 'cotton']))
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee($other->name);
    }

    public function test_q_takes_precedence_over_legacy_search_in_listing(): void
    {
        $tee = $this->product('Discovery Tee', 'DISCOVERY-TEE-1');
        $ignored = $this->product('Ignored Mug', 'IGNORED-MUG-1');
        $this->index($tee, $ignored);

        $this->get(route('storefront.shop.index', [
            'q' => 'tee',
            'search' => 'ignored',
        ]))
            ->assertOk()
            ->assertSee($tee->name)
            ->assertDontSee($ignored->name);
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

        return $product;
    }

    private function index(Product ...$products): void
    {
        foreach ($products as $product) {
            app(ProductSearchIndexer::class)->index($product->fresh());
        }
    }

    private function attribute(string $code): Attribute
    {
        return Attribute::query()->create([
            'code' => $code,
            'name' => ucfirst($code),
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
    }

    private function attributeValue(Attribute $attribute, string $label): AttributeValue
    {
        return AttributeValue::query()->create([
            'tenant_id' => $attribute->tenant_id,
            'attribute_id' => $attribute->id,
            'code' => app(AttributeValueService::class)->allocateCode($attribute->id, $label),
            'label' => $label,
            'position' => 0,
        ]);
    }
}
