<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Models\Category;
use Commerce\Core\Models\SearchDocument;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateVariantData;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\SearchSynonym;
use Commerce\Product\Services\ProductDiscoveryQuery;
use Commerce\Product\Services\ProductSearchIndexer;
use Commerce\Product\Services\SearchSynonymExpander;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductDiscoveryQueryTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_empty_query_does_not_read_search_documents(): void
    {
        $product = $this->product('Existing Product', 'SKU-EXISTING');
        $this->index($product);

        $query = app(ProductDiscoveryQuery::class);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->assertSame([], $query->candidateUuids('  '));

        $sql = array_column(DB::getQueryLog(), 'query');
        $this->assertFalse(
            collect($sql)->contains(
                static fn (string $query): bool => str_contains($query, 'search_documents'),
            ),
        );
    }

    public function test_highest_field_rank_is_not_cumulative(): void
    {
        $productA = $this->product('Cotton Shirt', 'SKU-A-001', 'Bright red finish');
        $productB = $this->product('Basic Shirt', 'SKU-B-001', 'Bright red cotton finish');
        $this->index($productA, $productB);

        $uuids = app(ProductDiscoveryQuery::class)->candidateUuids('red cotton');

        $this->assertSame($productA->uuid, $uuids[0]);
        $this->assertContains($productB->uuid, $uuids);
    }

    public function test_non_default_sku_exact_match_ranks_first(): void
    {
        $exactProduct = $this->product('Plain Product', 'SKU-DEFAULT-001');
        $exactProduct->update(['type' => 'variable']);
        app(ProductServiceInterface::class)->addVariant(new CreateVariantData(
            productUuid: $exactProduct->uuid,
            sku: 'SKU-SPECIAL-002',
            name: 'Special',
            price: 2500,
            position: 1,
        ));
        $nameProduct = $this->product('SKU-SPECIAL-002', 'SKU-OTHER-001');
        $this->index($exactProduct->fresh(), $nameProduct);

        $uuids = app(ProductDiscoveryQuery::class)->candidateUuids('sku-special-002');

        $this->assertSame($exactProduct->uuid, $uuids[0]);
        $this->assertContains($nameProduct->uuid, $uuids);
    }

    public function test_partial_tokens_do_not_match(): void
    {
        $product = $this->product('Cotton Tee', 'SKU-COTTON-001');
        $this->index($product);

        $this->assertSame([], app(ProductDiscoveryQuery::class)->candidateUuids('cot'));
    }

    public function test_all_expanded_tokens_must_match_across_fields(): void
    {
        $classicTee = $this->product('Classic Tee', 'SKU-TEE-001');
        $this->addAttribute($classicTee, 'Color', 'red', 'Red', 'Stored red');
        $this->addAttribute($classicTee, 'Material', 'cotton', 'Cotton', 'Stored cotton');

        $redJacket = $this->product('Red Jacket', 'SKU-JACKET-001');
        $this->addAttribute($redJacket, 'Material', 'polyester', 'Polyester', 'Stored polyester');
        $this->index($classicTee, $redJacket);

        $uuids = app(ProductDiscoveryQuery::class)->candidateUuids('red cotton tee');

        $this->assertSame([$classicTee->uuid], $uuids);
    }

    public function test_synonyms_are_applied_in_the_configured_direction_only(): void
    {
        $classicTee = $this->product('Classic Tee', 'SKU-TEE-002');
        $this->addAttribute($classicTee, 'Material', 'cotton', 'Cotton', 'Stored cotton');
        $thaiTee = $this->product('Thai Tee', 'SKU-TEE-THAI');
        $this->addAttribute($thaiTee, 'Material', 'thai-cotton', 'ผ้าฝ้าย', 'Stored Thai cotton');
        $this->index($classicTee, $thaiTee);
        SearchSynonym::query()->create([
            'from_term' => 'ผ้าฝ้าย',
            'to_term' => 'cotton',
        ]);
        app()->forgetInstance(SearchSynonymExpander::class);

        $query = app(ProductDiscoveryQuery::class);

        $this->assertSame([$classicTee->uuid], $query->candidateUuids('ผ้าฝ้าย tee'));
        $this->assertNotContains($thaiTee->uuid, $query->candidateUuids('cotton'));
    }

    public function test_attribute_labels_and_attached_category_names_are_indexed_and_searchable(): void
    {
        $product = $this->product('Summer Top', 'SKU-TOP-001');
        $this->addAttribute($product, 'Color', 'red', 'Crimson Red', 'Red');
        $category = Category::query()->create([
            'name' => 'Seasonal Apparel',
            'slug' => 'seasonal-apparel',
            'is_active' => true,
        ]);
        $product->categories()->attach($category->id);
        $this->index($product);

        $document = SearchDocument::query()
            ->where('index_name', ProductSearchIndexer::INDEX)
            ->where('document_id', $product->uuid)
            ->firstOrFail();

        $this->assertContains(
            ['code' => 'red', 'label' => 'Crimson Red'],
            $document->payload['attributes'],
        );
        $this->assertContains('Seasonal Apparel', $document->payload['category_names']);
        $this->assertSame([$product->uuid], app(ProductDiscoveryQuery::class)->candidateUuids('crimson'));
    }

    private function product(string $name, string $sku, string $description = ''): Product
    {
        $variant = $this->createPurchasableProduct(sku: $sku);
        $variant->product->update([
            'name' => $name,
            'description' => $description,
        ]);

        return $variant->product->fresh();
    }

    private function addAttribute(
        Product $product,
        string $attributeName,
        string $code,
        string $label,
        string $storedValue,
    ): void {
        $attribute = Attribute::query()->create([
            'code' => strtolower($attributeName).'-'.uniqid(),
            'name' => $attributeName,
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
        $attributeValue = AttributeValue::query()->create([
            'tenant_id' => $attribute->tenant_id,
            'attribute_id' => $attribute->id,
            'code' => $code,
            'label' => $label,
            'position' => 0,
        ]);

        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'product_variant_id' => $product->defaultVariant()?->id,
            'attribute_value_id' => $attributeValue->id,
            'value' => $storedValue,
        ]);
    }

    private function index(Product ...$products): void
    {
        foreach ($products as $product) {
            app(ProductSearchIndexer::class)->index($product->fresh());
        }
    }
}
