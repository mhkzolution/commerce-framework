<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Carbon\CarbonImmutable;
use Commerce\Cart\DTO\ShopFilterCatalog;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Core\Models\SearchDocument;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateVariantData;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\ProductVariant;
use Commerce\Product\Models\SearchSynonym;
use Commerce\Product\Services\ProductDiscoveryQuery;
use Commerce\Product\Services\ProductSearchIndexer;
use Commerce\Product\Services\SearchSynonymExpander;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class CatalogV2SearchDiscoveryRegressionTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_shop_request_loads_discovery_candidates_once_for_listing_and_facets(): void
    {
        $product = $this->product('Discovery Once Tee', 'DISCOVERY-ONCE');
        $this->index($product);

        $searchDocumentQueries = 0;
        DB::listen(static function ($query) use (&$searchDocumentQueries): void {
            if (str_contains(strtolower($query->sql), 'search_documents')) {
                $searchDocumentQueries++;
            }
        });

        $this->get(route('storefront.shop.index', ['q' => 'tee']))
            ->assertOk()
            ->assertSee($product->name);

        $this->assertSame(1, $searchDocumentQueries);
    }

    public function test_exact_sku_ranks_first_on_shop(): void
    {
        $exact = $this->product('Plain Alpha Product', 'EXACT-SKU-123');
        $titleMatch = $this->product('Exact-sku-123 Showcase', 'OTHER-SKU-123');
        $this->index($exact, $titleMatch);

        $this->get(route('storefront.shop.index', ['q' => 'exact-sku-123']))
            ->assertOk()
            ->assertSeeInOrder([$exact->name, $titleMatch->name]);
    }

    public function test_non_default_variant_sku_is_searchable_as_one_product_card(): void
    {
        $product = $this->product('Variable Search Product', 'DEFAULT-SKU-1');
        $product->update(['type' => 'variable']);
        app(ProductServiceInterface::class)->addVariant(new CreateVariantData(
            productUuid: $product->uuid,
            sku: 'NON-DEFAULT-SPECIAL',
            name: 'Special',
            price: 3200,
            position: 1,
        ));
        $this->index($product->fresh());

        $response = $this->get(route('storefront.shop.index', ['q' => 'non-default-special']))
            ->assertOk()
            ->assertSee($product->name);

        $this->assertSame(1, $response->viewData('products')->total());
    }

    public function test_color_code_survives_label_rename_and_only_affected_products_gain_new_label_search(): void
    {
        CarbonImmutable::setTestNow('2026-09-08 08:00:00');
        $color = $this->attribute('color', 'Color');
        $red = $this->value($color, 'red', 'Red');
        $renamed = $this->product('Rename Color Shirt', 'RENAME-RED');
        $unaffected = $this->product('Unaffected Shirt', 'RENAME-BLUE');
        $this->attachValue($renamed, $color, $red);
        $this->index($renamed, $unaffected);

        $affectedDocument = $this->document($renamed);
        $unaffectedUpdatedAt = $this->document($unaffected)->updated_at;
        $this->assertSame(
            0,
            $this->get(route('storefront.shop.index', ['q' => 'crimson']))
                ->assertOk()
                ->viewData('products')
                ->total(),
        );

        CarbonImmutable::setTestNow('2026-09-08 09:00:00');
        app(AttributeValueService::class)->update($red->uuid, 'Crimson Red');

        $this->assertSame('red', $red->fresh()->code);
        $this->assertTrue($this->document($renamed)->updated_at->greaterThan($affectedDocument->updated_at));
        $this->assertTrue($unaffectedUpdatedAt->equalTo($this->document($unaffected)->updated_at));
        $this->assertContains(
            ['code' => 'red', 'label' => 'Crimson Red'],
            $this->document($renamed)->payload['attributes'],
        );
        $this->get(route('storefront.shop.index', ['color' => 'red']))
            ->assertOk()
            ->assertSee($renamed->name);
        $this->get(route('storefront.shop.index', ['q' => 'crimson']))
            ->assertOk()
            ->assertSee($renamed->name)
            ->assertDontSee($unaffected->name);
    }

    public function test_synonym_replacement_is_directed_and_cud_does_not_rewrite_search_documents(): void
    {
        CarbonImmutable::setTestNow('2026-09-08 08:00:00');
        $material = $this->attribute('material', 'Material');
        $cotton = $this->value($material, 'cotton', 'Cotton');
        $thaiCotton = $this->value($material, 'thai-cotton', 'ผ้าฝ้าย');
        $classic = $this->product('Classic Tee', 'SYN-CLASSIC');
        $thai = $this->product('Thai Tee', 'SYN-THAI');
        $this->attachValue($classic, $material, $cotton);
        $this->attachValue($thai, $material, $thaiCotton);
        $this->index($classic, $thai);
        $documentUpdatedAt = $this->document($classic)->updated_at;

        CarbonImmutable::setTestNow('2026-09-08 09:00:00');
        $synonym = SearchSynonym::query()->create([
            'from_term' => 'ผ้าฝ้าย',
            'to_term' => 'cotton',
        ]);
        app()->forgetInstance(SearchSynonymExpander::class);

        $this->get(route('storefront.shop.index', ['q' => 'ผ้าฝ้าย tee']))
            ->assertOk()
            ->assertSee($classic->name)
            ->assertDontSee($thai->name);
        $this->assertNotContains(
            $thai->uuid,
            app(ProductDiscoveryQuery::class)->candidateUuids('cotton'),
        );
        $this->assertTrue($documentUpdatedAt->equalTo($this->document($classic)->updated_at));

        CarbonImmutable::setTestNow('2026-09-08 10:00:00');
        $synonym->update(['to_term' => 'linen']);
        $this->assertTrue($documentUpdatedAt->equalTo($this->document($classic)->updated_at));

        CarbonImmutable::setTestNow('2026-09-08 11:00:00');
        $synonym->delete();
        $this->assertTrue($documentUpdatedAt->equalTo($this->document($classic)->updated_at));
    }

    public function test_search_requires_all_tokens(): void
    {
        $color = $this->attribute('color', 'Color');
        $material = $this->attribute('material', 'Material');
        $red = $this->value($color, 'red', 'Red');
        $cotton = $this->value($material, 'cotton', 'Cotton');
        $polyester = $this->value($material, 'polyester', 'Polyester');
        $classic = $this->product('Classic Tee', 'AND-CLASSIC');
        $jacket = $this->product('Red Jacket', 'AND-JACKET');
        $this->attachValue($classic, $color, $red);
        $this->attachValue($classic, $material, $cotton);
        $this->attachValue($jacket, $color, $red);
        $this->attachValue($jacket, $material, $polyester);
        $this->index($classic, $jacket);

        $this->get(route('storefront.shop.index', ['q' => 'red cotton tee']))
            ->assertOk()
            ->assertSee($classic->name)
            ->assertDontSee($jacket->name);
    }

    public function test_empty_and_whitespace_q_bypass_discovery_for_listing_and_facets(): void
    {
        $color = $this->attribute('color', 'Color');
        $red = $this->value($color, 'red', 'Red');
        $product = $this->product('Browse Red Product', 'BROWSE-RED');
        $this->attachValue($product, $color, $red);
        $this->index($product);

        $searchDocumentQueries = 0;
        DB::listen(static function ($query) use (&$searchDocumentQueries): void {
            if (str_contains(strtolower($query->sql), 'search_documents')) {
                $searchDocumentQueries++;
            }
        });

        foreach (['', '   '] as $q) {
            $response = $this->get(route('storefront.shop.index', ['q' => $q]))
                ->assertOk()
                ->assertSee($product->name);
            $this->assertSame(['red' => 1], $this->facetCounts($response->viewData('filterCatalog'), 'color'));
        }

        $this->assertSame(0, $searchDocumentQueries);
    }

    public function test_color_code_filter_matches_any_variant_and_excludes_non_matching_products(): void
    {
        $color = $this->attribute('color', 'Color');
        $blue = $this->value($color, 'blue', 'Blue');
        $red = $this->value($color, 'red', 'Crimson Red');
        $matching = $this->product('Variable Crimson Shirt', 'VAR-BLUE');
        $matching->update(['type' => 'variable']);
        $redVariant = app(ProductServiceInterface::class)->addVariant(new CreateVariantData(
            productUuid: $matching->uuid,
            sku: 'VAR-RED',
            name: 'Red',
            price: 2500,
            position: 1,
        ));
        $other = $this->product('Blue Only Shirt', 'BLUE-ONLY');
        $this->attachAxisValues($matching, $color, [
            [$matching->defaultVariant(), $blue],
            [$redVariant, $red],
        ]);
        $this->attachAxisValues($other, $color, [
            [$other->defaultVariant(), $blue],
        ]);

        $this->get(route('storefront.shop.index', ['color' => 'red']))
            ->assertOk()
            ->assertSee($matching->name)
            ->assertDontSee($other->name);
    }

    public function test_search_scopes_facet_counts(): void
    {
        $color = $this->attribute('color', 'Color');
        $red = $this->value($color, 'red', 'Red');
        $blue = $this->value($color, 'blue', 'Blue');
        $redTee = $this->product('Facet Red Tee', 'FACET-RED-TEE');
        $blueTee = $this->product('Facet Blue Tee', 'FACET-BLUE-TEE');
        $parka = $this->product('Facet Red Parka', 'FACET-PARKA');
        $this->attachValue($redTee, $color, $red);
        $this->attachValue($blueTee, $color, $blue);
        $this->attachValue($parka, $color, $red);
        $this->index($redTee, $blueTee, $parka);

        $response = $this->get(route('storefront.shop.index', ['q' => 'tee']))
            ->assertOk()
            ->assertSee($redTee->name)
            ->assertSee($blueTee->name)
            ->assertDontSee($parka->name);

        $this->assertSame(
            ['blue' => 1, 'red' => 1],
            $this->facetCounts($response->viewData('filterCatalog'), 'color'),
        );
    }

    public function test_selected_facet_self_excludes_while_other_facets_keep_selection(): void
    {
        $color = $this->attribute('color', 'Color');
        $size = $this->attribute('size', 'Size');
        $red = $this->value($color, 'red', 'Red');
        $blue = $this->value($color, 'blue', 'Blue');
        $small = $this->value($size, 's', 'Small');
        $medium = $this->value($size, 'm', 'Medium');
        $redTee = $this->product('Self Exclude Red Tee', 'SELF-RED');
        $blueTee = $this->product('Self Exclude Blue Tee', 'SELF-BLUE');
        $this->attachValue($redTee, $color, $red);
        $this->attachValue($redTee, $size, $small);
        $this->attachValue($blueTee, $color, $blue);
        $this->attachValue($blueTee, $size, $medium);
        $this->index($redTee, $blueTee);

        $response = $this->get(route('storefront.shop.index', [
            'q' => 'tee',
            'color' => 'red',
        ]))
            ->assertOk()
            ->assertSee($redTee->name)
            ->assertDontSee($blueTee->name);
        $catalog = $response->viewData('filterCatalog');

        $this->assertSame(['blue' => 1, 'red' => 1], $this->facetCounts($catalog, 'color'));
        $this->assertSame(['s' => 1], $this->facetCounts($catalog, 'size'));
    }

    public function test_empty_search_candidates_return_empty_listing_and_facets(): void
    {
        $color = $this->attribute('color', 'Color');
        $red = $this->value($color, 'red', 'Red');
        $product = $this->product('No Hit Red Parka', 'NO-HIT-PARKA');
        $this->attachValue($product, $color, $red);
        $this->index($product);

        $response = $this->get(route('storefront.shop.index', ['q' => 'unfindable']))
            ->assertOk()
            ->assertDontSee($product->name)
            ->assertSee('storefront-empty', false);
        $catalog = $response->viewData('filterCatalog');

        $this->assertSame(0, $response->viewData('products')->total());
        $this->assertSame([], $catalog->brands);
        $this->assertTrue(collect($catalog->facets)->every(
            static fn (array $facet): bool => $facet['values'] === [],
        ));
    }

    public function test_shop_listing_follows_coverage_order(): void
    {
        $parka = $this->product('Cotton Parka', 'SHOP-COV-PARKA');
        $parka->update(['description' => 'Bright red finish']);
        $tee = $this->product('Red Cotton Tee', 'SHOP-COV-TEE');
        $this->index($parka->fresh(), $tee);

        $this->get(route('storefront.shop.index', ['q' => 'red cotton']))
            ->assertOk()
            ->assertSeeInOrder([$tee->name, $parka->name]);
    }

    public function test_price_asc_does_not_use_coverage_order(): void
    {
        $tee = $this->product('Red Cotton Tee', 'SHOP-PRICE-TEE', 9000);
        $parka = $this->product('Cotton Parka', 'SHOP-PRICE-PARKA', 1000);
        $parka->update(['description' => 'Bright red finish']);
        $this->index($tee, $parka->fresh());

        $this->get(route('storefront.shop.index', [
            'q' => 'red cotton',
            'sort' => 'price_asc',
        ]))
            ->assertOk()
            ->assertSeeInOrder([$parka->name, $tee->name]);
    }

    public function test_price_sort_overrides_discovery_ranking(): void
    {
        $exact = $this->product('Expensive Exact Product', 'PRICE-RANK', 9000);
        $titleMatch = $this->product('Price-rank Value Product', 'OTHER-PRICE', 1000);
        $this->index($exact, $titleMatch);

        $this->get(route('storefront.shop.index', [
            'q' => 'price-rank',
            'sort' => 'price_asc',
        ]))
            ->assertOk()
            ->assertSeeInOrder([$titleMatch->name, $exact->name]);
    }

    public function test_in_stock_search_preserves_stock_v1_visibility_rules(): void
    {
        $untracked = $this->product('Stock Tee Untracked', 'STOCK-UNTRACKED', stock: 1);
        app(InventoryServiceInterface::class)->setOnHand($untracked->defaultVariant()->uuid, 0);
        $untracked->defaultVariant()->update(['track_inventory' => false]);

        $allow = $this->product('Stock Tee Allow', 'STOCK-ALLOW', stock: 1);
        app(InventoryServiceInterface::class)->setOnHand($allow->defaultVariant()->uuid, 0);
        $allow->update(['backorder_policy' => 'allow']);

        $notify = $this->product('Stock Tee Notify', 'STOCK-NOTIFY', stock: 1);
        app(InventoryServiceInterface::class)->setOnHand($notify->defaultVariant()->uuid, 0);
        $notify->update(['backorder_policy' => 'notify']);

        $denied = $this->product('Stock Tee Denied', 'STOCK-DENIED', stock: 1);
        app(InventoryServiceInterface::class)->setOnHand($denied->defaultVariant()->uuid, 0);
        $this->index($untracked, $allow, $notify, $denied);

        $this->get(route('storefront.shop.index', [
            'q' => 'stock tee',
            'availability' => 'in_stock',
        ]))
            ->assertOk()
            ->assertSee($untracked->name)
            ->assertSee($allow->name)
            ->assertSee($notify->name)
            ->assertDontSee($denied->name);
    }

    private function product(string $name, string $sku, int $price = 2500, int $stock = 5): Product
    {
        $variant = $this->createPurchasableProduct(price: $price, stock: $stock, sku: $sku);
        $variant->product->update(['name' => $name]);

        return $variant->product->fresh();
    }

    private function attribute(string $code, string $name): Attribute
    {
        return Attribute::query()->create([
            'code' => $code,
            'name' => $name,
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
    }

    private function value(Attribute $attribute, string $code, string $label): AttributeValue
    {
        return AttributeValue::query()->create([
            'tenant_id' => $attribute->tenant_id,
            'attribute_id' => $attribute->id,
            'code' => $code,
            'label' => $label,
            'position' => (int) AttributeValue::query()
                ->where('attribute_id', $attribute->id)
                ->max('position') + 1,
        ]);
    }

    private function attachValue(Product $product, Attribute $attribute, AttributeValue $value): void
    {
        ProductAttribute::query()->firstOrCreate([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
        ], [
            'used_for_variations' => false,
            'position' => 0,
        ]);
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'product_variant_id' => null,
            'attribute_value_id' => $value->id,
            'value' => 'legacy-'.$value->code,
        ]);
    }

    /**
     * @param  list<array{0: ProductVariant|null, 1: AttributeValue}>  $values
     */
    private function attachAxisValues(Product $product, Attribute $attribute, array $values): void
    {
        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'used_for_variations' => true,
            'position' => 0,
        ]);

        foreach ($values as [$variant, $value]) {
            $this->assertNotNull($variant);
            ProductAttributeValue::query()->create([
                'product_id' => $product->id,
                'attribute_id' => $attribute->id,
                'product_variant_id' => $variant->id,
                'attribute_value_id' => $value->id,
                'value' => 'legacy-'.$value->code,
            ]);
        }
    }

    private function index(Product ...$products): void
    {
        foreach ($products as $product) {
            app(ProductSearchIndexer::class)->index($product->fresh());
        }
    }

    private function document(Product $product): SearchDocument
    {
        return SearchDocument::query()
            ->where('index_name', ProductSearchIndexer::INDEX)
            ->where('document_id', $product->uuid)
            ->firstOrFail();
    }

    /**
     * @return array<string, int>
     */
    private function facetCounts(ShopFilterCatalog $catalog, string $code): array
    {
        $facet = collect($catalog->facets)->firstWhere('code', $code);

        if (! is_array($facet)) {
            return [];
        }

        return collect($facet['values'])
            ->mapWithKeys(static fn (array $value): array => [$value['code'] => $value['count']])
            ->sortKeys()
            ->all();
    }
}
