<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Cart\DTO\ShopFilterCatalog;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttributeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ShopFacetUniverseTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_size_param_does_not_match_shoe_size(): void
    {
        [$apparel, $shoe] = $this->sizeAndShoeProducts();

        $this->get(route('storefront.shop.index', ['size' => 's']))
            ->assertOk()
            ->assertSee($apparel->name)
            ->assertDontSee($shoe->name);

        $this->get(route('storefront.shop.index', ['shoe_size' => 's']))
            ->assertOk()
            ->assertSee($shoe->name)
            ->assertDontSee($apparel->name);
    }

    public function test_size_and_shoe_size_facets_do_not_share_counts(): void
    {
        [$apparel, $shoe] = $this->sizeAndShoeProducts();

        $catalog = $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->viewData('filterCatalog');

        $this->assertInstanceOf(ShopFilterCatalog::class, $catalog);
        $this->assertSame(['s' => 1], $this->facetCounts($catalog, 'size'));
        $this->assertSame(['s' => 1], $this->facetCounts($catalog, 'shoe_size'));
        $this->assertNotSame($apparel->id, $shoe->id);
    }

    public function test_unknown_and_reserved_params_do_not_abort_or_attribute_filter(): void
    {
        $visible = $this->sizeAndShoeProducts()[0];

        $this->get(route('storefront.shop.index', ['foo' => 'bar']))
            ->assertOk()
            ->assertSee($visible->name);

        $this->get(route('storefront.shop.index', ['page' => 2]))
            ->assertOk();

        $this->get(route('storefront.shop.index', ['brand' => 'red']))
            ->assertOk();
    }

    /**
     * @return array{Product, Product}
     */
    private function sizeAndShoeProducts(): array
    {
        $size = $this->attribute('size', 'Size');
        $shoeSize = $this->attribute('shoe_size', 'Shoe size');
        $apparel = $this->product('Apparel Size Small', 'FACET-APPAREL-S');
        $shoe = $this->product('Shoe Size Small', 'FACET-SHOE-S');

        $this->attachAttributeValue($apparel, $size, 'Small', 's');
        $this->attachAttributeValue($shoe, $shoeSize, 'Small', 's');

        return [$apparel, $shoe];
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

    private function product(string $name, string $sku): Product
    {
        $created = $this->createPurchasableProduct(stock: 5, sku: $sku);
        $created->product->update(['name' => $name]);

        return $created->product->fresh();
    }

    private function attachAttributeValue(
        Product $product,
        Attribute $attribute,
        string $label,
        string $code,
    ): AttributeValue {
        $value = AttributeValue::query()->create([
            'tenant_id' => $attribute->tenant_id,
            'attribute_id' => $attribute->id,
            'code' => $code,
            'label' => $label,
            'position' => (int) AttributeValue::query()->where('attribute_id', $attribute->id)->max('position') + 1,
        ]);

        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'product_variant_id' => null,
            'attribute_value_id' => $value->id,
            'value' => $value->label,
        ]);

        return $value;
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
