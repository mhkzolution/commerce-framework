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

    public function test_coverage_uses_winning_field_only(): void
    {
        $bothInName = $this->product('Red Cotton Shirt', 'SKU-COV-BOTH');
        $cottonInName = $this->product('Cotton Shirt', 'SKU-COV-NAME', 'Bright red finish');
        $this->index($bothInName, $cottonInName);

        $this->assertSame(
            [$bothInName->uuid, $cottonInName->uuid],
            app(ProductDiscoveryQuery::class)->candidateUuids('red cotton'),
        );
    }

    public function test_same_field_higher_coverage_beats_title_order(): void
    {
        $parka = $this->product('Cotton Parka', 'SKU-COV-PARKA', 'Bright red finish');
        $tee = $this->product('Red Cotton Tee', 'SKU-COV-TEE');
        $this->index($parka, $tee);

        $this->assertSame(
            [$tee->uuid, $parka->uuid],
            app(ProductDiscoveryQuery::class)->candidateUuids('red cotton'),
        );
    }

    public function test_coverage_counts_distinct_query_tokens(): void
    {
        $two = $this->product('Red Cotton Tee', 'SKU-DIST-TWO');
        $one = $this->product('Cotton Shirt', 'SKU-DIST-ONE', 'Bright red finish');
        $this->index($two, $one);

        $this->assertSame(
            [$two->uuid, $one->uuid],
            app(ProductDiscoveryQuery::class)->candidateUuids('red red cotton'),
        );
    }

    public function test_equal_coverage_falls_back_to_title(): void
    {
        $zebra = $this->product('Zebra Cotton', 'SKU-EQ-ZEBRA');
        $alpha = $this->product('Alpha Cotton', 'SKU-EQ-ALPHA');
        $this->index($zebra, $alpha);

        $this->assertSame(
            [$alpha->uuid, $zebra->uuid],
            app(ProductDiscoveryQuery::class)->candidateUuids('cotton'),
        );
    }

    public function test_exact_sku_hits_sort_by_title_and_ignore_name_coverage(): void
    {
        SearchDocument::query()->create([
            'index_name' => ProductSearchIndexer::INDEX,
            'document_id' => 'sku-alpha',
            'title' => 'Alpha Widget',
            'body' => '',
            'payload' => ['skus' => ['RED COTTON'], 'attributes' => []],
        ]);
        SearchDocument::query()->create([
            'index_name' => ProductSearchIndexer::INDEX,
            'document_id' => 'sku-zed',
            'title' => 'Zed Red Cotton',
            'body' => '',
            'payload' => ['skus' => ['RED COTTON'], 'attributes' => []],
        ]);

        $this->assertSame(
            ['sku-alpha', 'sku-zed'],
            app(ProductDiscoveryQuery::class)->candidateUuids('RED COTTON'),
        );
    }

    public function test_coverage_uses_replaced_expanded_tokens(): void
    {
        $cottonTee = $this->product('Cotton Tee', 'SKU-EXP-COTTON');
        $teeShirt = $this->product('Tee Shirt', 'SKU-EXP-TEE', 'Premium cotton lining');
        $this->index($cottonTee, $teeShirt);
        SearchSynonym::query()->create([
            'from_term' => 'ผ้าฝ้าย',
            'to_term' => 'cotton',
        ]);
        app()->forgetInstance(SearchSynonymExpander::class);

        $this->assertSame(
            [$cottonTee->uuid, $teeShirt->uuid],
            app(ProductDiscoveryQuery::class)->candidateUuids('ผ้าฝ้าย tee'),
        );
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

    public function test_sku_words_only_match_as_a_full_sku(): void
    {
        $product = $this->product('Widget', 'SKU-DEFAULT-002');
        $product->update(['type' => 'variable']);
        app(ProductServiceInterface::class)->addVariant(new CreateVariantData(
            productUuid: $product->uuid,
            sku: 'RED COTTON',
            name: 'Extra',
            price: 2500,
            position: 1,
        ));
        $this->index($product->fresh());

        $query = app(ProductDiscoveryQuery::class);

        $this->assertNotContains($product->uuid, $query->candidateUuids('red'));
        $this->assertNotContains($product->uuid, $query->candidateUuids('cotton'));
        $this->assertContains($product->uuid, $query->candidateUuids('RED COTTON'));
        $this->assertContains($product->uuid, $query->candidateUuids('red cotton'));
    }

    public function test_sku_text_in_description_remains_searchable(): void
    {
        $product = $this->product('Widget', 'RED', 'RED finish');
        $multiwordSkuProduct = $this->product('Bundle', 'RED COTTON', 'RED COTTON finish');
        $this->index($product, $multiwordSkuProduct);

        $query = app(ProductDiscoveryQuery::class);

        $this->assertContains($product->uuid, $query->candidateUuids('red finish'));
        $this->assertContains($multiwordSkuProduct->uuid, $query->candidateUuids('red cotton finish'));
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

    public function test_candidate_cap_keeps_rank_prefix_and_drops_the_tail(): void
    {
        $ids = [];

        for ($i = 0; $i <= ProductDiscoveryQuery::CANDIDATE_CAP; $i++) {
            $uuid = sprintf('cap-%03d', $i);
            $ids[] = $uuid;
            SearchDocument::query()->create([
                'index_name' => ProductSearchIndexer::INDEX,
                'document_id' => $uuid,
                'title' => sprintf('Cap %03d', $i),
                'body' => '',
                'payload' => ['skus' => [], 'attributes' => []],
            ]);
        }

        $uuids = app(ProductDiscoveryQuery::class)->candidateUuids('cap');

        $this->assertCount(ProductDiscoveryQuery::CANDIDATE_CAP, $uuids);
        $this->assertSame(array_slice($ids, 0, ProductDiscoveryQuery::CANDIDATE_CAP), $uuids);
        $this->assertNotContains(sprintf('cap-%03d', ProductDiscoveryQuery::CANDIDATE_CAP), $uuids);
    }

    public function test_candidate_cap_does_not_limit_the_sql_scan(): void
    {
        SearchDocument::query()->create([
            'index_name' => ProductSearchIndexer::INDEX,
            'document_id' => 'cap-sql',
            'title' => 'Cap',
            'body' => '',
            'payload' => ['skus' => [], 'attributes' => []],
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(ProductDiscoveryQuery::class)->candidateUuids('cap');
        $sql = array_column(DB::getQueryLog(), 'query');

        $this->assertFalse(
            collect($sql)->contains(
                static fn (string $query): bool => str_contains(strtolower($query), 'limit')
                    && str_contains($query, 'search_documents'),
            ),
        );
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
