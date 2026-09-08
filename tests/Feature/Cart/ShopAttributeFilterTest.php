<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use Commerce\Cart\DTO\ShopFilterCatalog;
use Commerce\Cart\DTO\ShopListingFilters;
use Commerce\Cart\Services\ShopProductQuery;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateVariantData;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ShopAttributeFilterTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(IamSeeder::class);
    }

    public function test_variable_product_matches_color_code_when_any_variant_is_red(): void
    {
        $setup = $this->variableProductDefaultBlueExtraRed();

        $this->get(route('storefront.shop.index', ['color' => 'red']))
            ->assertOk()
            ->assertSee($setup['product']->name);
    }

    public function test_simple_product_matches_non_axis_material_by_value_code(): void
    {
        $setup = $this->simpleProductWithMaterialCotton();

        $results = app(ShopProductQuery::class)->paginate(
            new ShopListingFilters(attributes: [
                $setup['material']->code => $setup['cotton']->code,
            ]),
            new ShopFilterCatalog,
        );

        $this->assertTrue(
            $results->getCollection()->contains(
                static fn (Product $product): bool => $product->id === $setup['product']->id,
            ),
        );
    }

    public function test_default_variant_does_not_control_axis_color_match(): void
    {
        $setup = $this->variableProductDefaultBlueExtraRed();

        $this->assertTrue($setup['blue']->is_default);
        $this->assertFalse($setup['red']->is_default);
        $this->assertSame(
            $setup['blueValue']->id,
            ProductAttributeValue::query()
                ->where('product_variant_id', $setup['blue']->id)
                ->where('attribute_id', $setup['color']->id)
                ->value('attribute_value_id'),
        );

        $this->get(route('storefront.shop.index', ['color' => 'red']))
            ->assertOk()
            ->assertSee($setup['product']->name);
    }

    public function test_filter_facets_prefer_attribute_value_codes(): void
    {
        $this->variableProductDefaultBlueExtraRed();

        $html = $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="red"', $html);
        $this->assertStringContainsString('value="blue"', $html);
    }

    /**
     * @return array{
     *     product: Product,
     *     color: Attribute,
     *     blue: ProductVariant,
     *     red: ProductVariant,
     *     blueValue: AttributeValue
     * }
     */
    private function variableProductDefaultBlueExtraRed(): array
    {
        $color = Attribute::query()->create([
            'code' => 'color',
            'name' => 'Color',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);

        $blueValue = $this->createAttributeValue($color, 'Blue', 0);
        $redValue = $this->createAttributeValue($color, 'Red', 1);

        $blue = $this->createPurchasableProduct(sku: 'VAR-BLUE');
        $product = $blue->product;
        $product->update(['type' => 'variable']);
        $blue->update(['sku' => 'VAR-BLUE']);

        $red = app(ProductServiceInterface::class)->addVariant(new CreateVariantData(
            productUuid: $product->uuid,
            sku: 'VAR-RED',
            name: 'Red',
            price: 2500,
            position: 1,
        ));

        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'used_for_variations' => true,
            'position' => 0,
        ]);

        $this->attachMembership($product, $color, $blueValue);
        $this->attachMembership($product, $color, $redValue);
        $this->attachVariantValue($product, $blue, $color, $blueValue);
        $this->attachVariantValue($product, $red, $color, $redValue);

        return [
            'product' => $product->fresh(),
            'color' => $color,
            'blue' => $blue->fresh(),
            'red' => $red->fresh(),
            'blueValue' => $blueValue,
        ];
    }

    /**
     * @return array{product: Product, material: Attribute, cotton: AttributeValue}
     */
    private function simpleProductWithMaterialCotton(): array
    {
        $material = Attribute::query()->create([
            'code' => 'material',
            'name' => 'Material',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);

        $cotton = $this->createAttributeValue($material, 'Cotton', 0);

        $variant = $this->createPurchasableProduct(sku: 'SIMPLE-COTTON');
        $product = $variant->product;

        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $material->id,
            'used_for_variations' => false,
            'position' => 0,
        ]);

        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $material->id,
            'product_variant_id' => null,
            'attribute_value_id' => $cotton->id,
            'value' => $cotton->label,
        ]);

        return [
            'product' => $product->fresh(),
            'material' => $material,
            'cotton' => $cotton,
        ];
    }

    private function createAttributeValue(Attribute $attribute, string $label, int $position): AttributeValue
    {
        return AttributeValue::query()->create([
            'tenant_id' => $attribute->tenant_id,
            'attribute_id' => $attribute->id,
            'code' => app(AttributeValueService::class)->allocateCode($attribute->id, $label),
            'label' => $label,
            'position' => $position,
        ]);
    }

    private function attachMembership(Product $product, Attribute $attribute, AttributeValue $value): void
    {
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'product_variant_id' => null,
            'attribute_value_id' => $value->id,
            'value' => $value->label,
        ]);
    }

    private function attachVariantValue(
        Product $product,
        ProductVariant $variant,
        Attribute $attribute,
        AttributeValue $value,
    ): void {
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'product_variant_id' => $variant->id,
            'attribute_value_id' => $value->id,
            'value' => $value->label,
        ]);
    }
}
