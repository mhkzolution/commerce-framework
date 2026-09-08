<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use Commerce\Cart\DTO\ShopFilterCatalog;
use Commerce\Cart\DTO\ShopListingFilters;
use Commerce\Cart\Services\ShopFilterCatalogService;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ShopFacetCountTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_color_self_excludes_while_size_keeps_the_color_filter_in_the_search_set(): void
    {
        $color = $this->attribute('color', 'Color');
        $size = $this->attribute('size', 'Size');
        $red = $this->value($color, 'red', 'Red');
        $blue = $this->value($color, 'blue', 'Blue');
        $small = $this->value($size, 's', 'Small');
        $medium = $this->value($size, 'm', 'Medium');
        $redTee = $this->product('Red Tee', 'FACET-RED-TEE');
        $blueTee = $this->product('Blue Tee', 'FACET-BLUE-TEE');
        $this->attachAxisValue($redTee, $color, $red);
        $this->attachAxisValue($redTee, $size, $small);
        $this->attachAxisValue($blueTee, $color, $blue);
        $this->attachAxisValue($blueTee, $size, $medium);
        $this->index($redTee, $blueTee);

        $catalog = $this->build(
            new ShopListingFilters(q: 'tee', color: 'red'),
            [$redTee->uuid, $blueTee->uuid],
        );

        $this->assertSame(['blue' => 1, 'red' => 1], $this->facetCounts($catalog, 'color'));
        $this->assertSame(['s' => 1], $this->facetCounts($catalog, 'size'));
    }

    public function test_search_set_scopes_all_attribute_facet_counts(): void
    {
        $color = $this->attribute('color', 'Color');
        $size = $this->attribute('size', 'Size');
        $red = $this->value($color, 'red', 'Red');
        $blue = $this->value($color, 'blue', 'Blue');
        $small = $this->value($size, 's', 'Small');
        $medium = $this->value($size, 'm', 'Medium');
        $redTee = $this->product('Red Tee', 'SEARCH-RED-TEE');
        $blueTee = $this->product('Blue Tee', 'SEARCH-BLUE-TEE');
        $parka = $this->product('Parka', 'SEARCH-PARKA');
        $this->attachValue($redTee, $color, $red);
        $this->attachValue($redTee, $size, $small);
        $this->attachValue($blueTee, $color, $blue);
        $this->attachValue($blueTee, $size, $medium);
        $this->attachValue($parka, $color, $red);
        $this->index($redTee, $blueTee, $parka);

        $catalog = $this->build(
            new ShopListingFilters(q: 'tee'),
            [$redTee->uuid, $blueTee->uuid],
        );

        $this->assertSame(['blue' => 1, 'red' => 1], $this->facetCounts($catalog, 'color'));
        $this->assertSame(['m' => 1, 's' => 1], $this->facetCounts($catalog, 'size'));
    }

    public function test_additional_attribute_self_excludes_and_restricts_other_facets_in_browse(): void
    {
        $material = $this->attribute('material', 'Material');
        $color = $this->attribute('color', 'Color');
        $cotton = $this->value($material, 'cotton', 'Cotton');
        $linen = $this->value($material, 'linen', 'Linen');
        $red = $this->value($color, 'red', 'Red');
        $blue = $this->value($color, 'blue', 'Blue');
        $cottonShirt = $this->product('Cotton Shirt', 'MATERIAL-COTTON');
        $linenShirt = $this->product('Linen Shirt', 'MATERIAL-LINEN');
        $this->attachValue($cottonShirt, $material, $cotton);
        $this->attachValue($cottonShirt, $color, $red);
        $this->attachValue($linenShirt, $material, $linen);
        $this->attachValue($linenShirt, $color, $blue);

        $catalog = $this->build(
            new ShopListingFilters(attributes: ['material' => 'cotton']),
            null,
        );

        $this->assertSame(['cotton' => 1, 'linen' => 1], $this->facetCounts($catalog, 'material'));
        $this->assertSame(['red' => 1], $this->facetCounts($catalog, 'color'));
    }

    public function test_brand_self_excludes_while_attribute_facets_keep_the_brand_filter(): void
    {
        $nike = $this->brand('Nike', 'nike');
        $acme = $this->brand('Acme', 'acme');
        $color = $this->attribute('color', 'Color');
        $red = $this->value($color, 'red', 'Red');
        $blue = $this->value($color, 'blue', 'Blue');
        $nikeTee = $this->product('Nike Red Tee', 'BRAND-NIKE');
        $acmeTee = $this->product('Acme Blue Tee', 'BRAND-ACME');
        $nikeTee->update(['brand_uuid' => $nike->uuid]);
        $acmeTee->update(['brand_uuid' => $acme->uuid]);
        $this->attachValue($nikeTee, $color, $red);
        $this->attachValue($acmeTee, $color, $blue);

        $catalog = $this->build(new ShopListingFilters(brand: 'nike'), null);

        $this->assertSame(['acme' => 1, 'nike' => 1], $this->brandCounts($catalog));
        $this->assertSame(['red' => 1], $this->facetCounts($catalog, 'color'));
    }

    public function test_empty_and_whitespace_queries_browse_without_reading_search_documents(): void
    {
        $color = $this->attribute('color', 'Color');
        $red = $this->value($color, 'red', 'Red');
        $blue = $this->value($color, 'blue', 'Blue');
        $first = $this->product('First Product', 'BROWSE-FIRST');
        $second = $this->product('Second Product', 'BROWSE-SECOND');
        $this->attachValue($first, $color, $red);
        $this->attachValue($second, $color, $blue);
        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $empty = $this->build(new ShopListingFilters(q: ''), null);
        $whitespace = $this->build(new ShopListingFilters(q: '   '), null);

        $this->assertSame(['blue' => 1, 'red' => 1], $this->facetCounts($empty, 'color'));
        $this->assertSame(['blue' => 1, 'red' => 1], $this->facetCounts($whitespace, 'color'));
        $this->assertFalse(collect($queries)->contains(
            static fn (string $sql): bool => str_contains($sql, 'search_documents'),
        ));
    }

    public function test_category_filter_is_kept_when_counting_attribute_facets(): void
    {
        $shirts = Category::query()->create(['name' => 'Shirts', 'slug' => 'shirts', 'is_active' => true]);
        $jackets = Category::query()->create(['name' => 'Jackets', 'slug' => 'jackets', 'is_active' => true]);
        $color = $this->attribute('color', 'Color');
        $red = $this->value($color, 'red', 'Red');
        $blue = $this->value($color, 'blue', 'Blue');
        $shirt = $this->product('Red Shirt', 'CATEGORY-SHIRT');
        $jacket = $this->product('Blue Jacket', 'CATEGORY-JACKET');
        $shirt->categories()->attach($shirts->id);
        $jacket->categories()->attach($jackets->id);
        $this->attachValue($shirt, $color, $red);
        $this->attachValue($jacket, $color, $blue);

        $catalog = $this->build(new ShopListingFilters(category: 'shirts'), null);

        $this->assertSame(['red' => 1], $this->facetCounts($catalog, 'color'));
    }

    public function test_in_stock_filter_is_kept_when_counting_attribute_facets(): void
    {
        $color = $this->attribute('color', 'Color');
        $red = $this->value($color, 'red', 'Red');
        $blue = $this->value($color, 'blue', 'Blue');
        $inStock = $this->product('In Stock Red Tee', 'STOCK-RED', 5);
        $outOfStock = $this->product('Out Of Stock Blue Tee', 'STOCK-BLUE', 1);
        app(InventoryServiceInterface::class)->setOnHand($outOfStock->defaultVariant()->uuid, 0);
        $this->attachValue($inStock, $color, $red);
        $this->attachValue($outOfStock, $color, $blue);

        $catalog = $this->build(new ShopListingFilters(availability: 'in_stock'), null);

        $this->assertSame(['red' => 1], $this->facetCounts($catalog, 'color'));
    }

    public function test_empty_non_empty_query_candidate_set_produces_empty_facets(): void
    {
        $color = $this->attribute('color', 'Color');
        $red = $this->value($color, 'red', 'Red');
        $hidden = $this->product('Hidden', 'NO-HITS');
        $this->attachValue($hidden, $color, $red);
        $this->index($hidden);

        $catalog = $this->build(new ShopListingFilters(q: 'tee'), []);

        $this->assertSame([], $this->facetCounts($catalog, 'color'));
        $this->assertSame([], $catalog->brands);
    }

    public function test_legacy_colors_include_values_from_grouped_colour_attribute(): void
    {
        $colour = $this->attribute('colour', 'Colour');
        $navy = $this->value($colour, 'navy', 'Navy');
        $product = $this->product('Navy Shirt', 'COLOUR-NAVY');
        $this->attachValue($product, $colour, $navy);

        $catalog = $this->build(new ShopListingFilters, null);

        $this->assertSame(['navy' => 'Navy'], $catalog->colors);
    }

    private function build(ShopListingFilters $filters, ?array $searchUuids): ShopFilterCatalog
    {
        return app(ShopFilterCatalogService::class)->buildFor($filters, $searchUuids);
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
            'position' => (int) AttributeValue::query()->where('attribute_id', $attribute->id)->max('position') + 1,
        ]);
    }

    private function product(string $name, string $sku, int $stock = 5): Product
    {
        $variant = $this->createPurchasableProduct(stock: $stock, sku: $sku);
        $variant->product->update(['name' => $name]);

        return $variant->product->fresh();
    }

    private function attachValue(Product $product, Attribute $attribute, AttributeValue $value): void
    {
        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'used_for_variations' => false,
            'position' => 0,
        ]);
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'product_variant_id' => null,
            'attribute_value_id' => $value->id,
            'value' => $value->label,
        ]);
    }

    private function attachAxisValue(Product $product, Attribute $attribute, AttributeValue $value): void
    {
        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'used_for_variations' => true,
            'position' => 0,
        ]);
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'product_variant_id' => $product->defaultVariant()->id,
            'attribute_value_id' => $value->id,
            'value' => $value->label,
        ]);
    }

    private function brand(string $name, string $slug): Brand
    {
        return Brand::query()->create(['name' => $name, 'slug' => $slug, 'is_active' => true]);
    }

    private function index(Product ...$products): void
    {
        foreach ($products as $product) {
            app(ProductSearchIndexer::class)->index($product->fresh(['variants', 'categories']));
        }
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

    /**
     * @return array<string, int>
     */
    private function brandCounts(ShopFilterCatalog $catalog): array
    {
        return collect($catalog->brands)
            ->mapWithKeys(static fn (array $brand): array => [$brand['slug'] => $brand['count']])
            ->sortKeys()
            ->all();
    }
}
