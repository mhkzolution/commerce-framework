<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateVariantData;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\ProductVariant;
use Commerce\Product\Services\VariantIdentity;
use Commerce\Product\Services\VariantMatrixGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class VariantMatrixGeneratorTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_generate_creates_four_identities_and_keeps_edited_blue_m_after_adding_l(): void
    {
        $setup = $this->variableProductWithColorSize();
        $product = $setup['product'];
        $generator = app(VariantMatrixGenerator::class);

        $first = $generator->generate($product);

        $this->assertCount(4, $product->fresh()->variants);
        $this->assertCount(2, $first['keep']);
        $this->assertCount(2, $first['create']);
        $this->assertSame([], $first['drop']);

        $redSKey = VariantIdentity::key([$setup['red']->id, $setup['small']->id]);
        $blueMKey = VariantIdentity::key([$setup['blue']->id, $setup['medium']->id]);
        $redMKey = VariantIdentity::key([$setup['red']->id, $setup['medium']->id]);
        $blueSKey = VariantIdentity::key([$setup['blue']->id, $setup['small']->id]);

        $this->assertSame($setup['redS']->uuid, $first['keep'][$redSKey]->uuid);
        $this->assertSame($setup['blueM']->uuid, $first['keep'][$blueMKey]->uuid);
        $this->assertSame($setup['redS']->sku, $first['keep'][$redSKey]->sku);
        $this->assertSame('BLUE-M', $first['keep'][$blueMKey]->sku);
        $this->assertArrayHasKey($redMKey, $first['create']);
        $this->assertArrayHasKey($blueSKey, $first['create']);

        $this->assertSame(
            $blueMKey,
            VariantIdentity::key([$setup['medium']->id, $setup['blue']->id]),
        );
        $this->assertStringNotContainsString(
            (string) $setup['cotton']->id,
            $blueMKey,
        );

        $blueM = $first['keep'][$blueMKey];
        $blueM->update(['sku' => 'CUSTOM-BLUE-M']);
        $blueMUuid = $blueM->uuid;

        $large = $this->createAttributeValue($setup['size'], 'L', 2);
        $this->attachMembership($product, $setup['size'], $large);

        $second = $generator->generate($product->fresh());

        $this->assertCount(6, $product->fresh()->variants);
        $this->assertArrayHasKey($blueMKey, $second['keep']);
        $this->assertSame($blueMUuid, $second['keep'][$blueMKey]->uuid);
        $this->assertSame('CUSTOM-BLUE-M', $second['keep'][$blueMKey]->sku);
        $this->assertSame('CUSTOM-BLUE-M', $blueM->fresh()->sku);

        $redLKey = VariantIdentity::key([$setup['red']->id, $large->id]);
        $blueLKey = VariantIdentity::key([$setup['blue']->id, $large->id]);
        $this->assertArrayHasKey($redLKey, $second['create']);
        $this->assertArrayHasKey($blueLKey, $second['create']);
        $this->assertCount(2, $second['create']);
        $this->assertSame([], $second['drop']);
    }

    public function test_generate_returns_drop_candidates_without_deleting(): void
    {
        $setup = $this->variableProductWithColorSize();
        $product = $setup['product'];
        $generator = app(VariantMatrixGenerator::class);

        $generator->generate($product);

        ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $setup['color']->id)
            ->whereNull('product_variant_id')
            ->where('attribute_value_id', $setup['blue']->id)
            ->delete();

        $result = $generator->generate($product->fresh());

        $blueMKey = VariantIdentity::key([$setup['blue']->id, $setup['medium']->id]);
        $blueSKey = VariantIdentity::key([$setup['blue']->id, $setup['small']->id]);

        $this->assertArrayHasKey($blueMKey, $result['drop']);
        $this->assertArrayHasKey($blueSKey, $result['drop']);
        $this->assertCount(2, $result['keep']);
        $this->assertSame([], $result['create']);

        $this->assertNotNull(ProductVariant::query()->find($setup['blueM']->id));
        $this->assertNull($setup['blueM']->fresh()->deleted_at);
        $this->assertSame(4, $product->fresh()->variants()->count());
        $this->assertSame(0, $product->variants()->onlyTrashed()->count());
    }

    /**
     * @return array{
     *     product: Product,
     *     color: Attribute,
     *     size: Attribute,
     *     material: Attribute,
     *     red: AttributeValue,
     *     blue: AttributeValue,
     *     small: AttributeValue,
     *     medium: AttributeValue,
     *     cotton: AttributeValue,
     *     redS: ProductVariant,
     *     blueM: ProductVariant
     * }
     */
    private function variableProductWithColorSize(): array
    {
        $color = Attribute::query()->create([
            'code' => 'color-'.uniqid(),
            'name' => 'Color',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
        $size = Attribute::query()->create([
            'code' => 'size-'.uniqid(),
            'name' => 'Size',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
        $material = Attribute::query()->create([
            'code' => 'material-'.uniqid(),
            'name' => 'Material',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);

        $set = AttributeSet::query()->create([
            'code' => 'apparel-'.uniqid(),
            'name' => 'Apparel',
        ]);
        $set->attributes()->attach([
            $color->id => ['position' => 0, 'is_required' => false],
            $size->id => ['position' => 1, 'is_required' => false],
            $material->id => ['position' => 2, 'is_required' => false],
        ]);

        $red = $this->createAttributeValue($color, 'Red', 0);
        $blue = $this->createAttributeValue($color, 'Blue', 1);
        $small = $this->createAttributeValue($size, 'S', 0);
        $medium = $this->createAttributeValue($size, 'M', 1);
        $cotton = $this->createAttributeValue($material, 'Cotton', 0);

        $redS = $this->createPurchasableProduct(sku: 'RED-S');
        $product = $redS->product;
        $product->update([
            'type' => 'variable',
            'attribute_set_id' => $set->id,
        ]);
        $redS->update(['sku' => 'RED-S']);

        $blueM = app(ProductServiceInterface::class)->addVariant(new CreateVariantData(
            productUuid: $product->uuid,
            sku: 'BLUE-M',
            name: 'Blue M',
            price: 2500,
            position: 1,
        ));

        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'used_for_variations' => true,
            'position' => 0,
        ]);
        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $size->id,
            'used_for_variations' => true,
            'position' => 1,
        ]);
        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $material->id,
            'used_for_variations' => false,
            'position' => 2,
        ]);

        $this->attachMembership($product, $color, $red);
        $this->attachMembership($product, $color, $blue);
        $this->attachMembership($product, $size, $small);
        $this->attachMembership($product, $size, $medium);
        $this->attachMembership($product, $material, $cotton);

        $this->attachVariantValue($product, $redS, $color, $red);
        $this->attachVariantValue($product, $redS, $size, $small);
        $this->attachVariantValue($product, $blueM, $color, $blue);
        $this->attachVariantValue($product, $blueM, $size, $medium);

        return [
            'product' => $product->fresh(),
            'color' => $color,
            'size' => $size,
            'material' => $material,
            'red' => $red,
            'blue' => $blue,
            'small' => $small,
            'medium' => $medium,
            'cotton' => $cotton,
            'redS' => $redS->fresh(),
            'blueM' => $blueM->fresh(),
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
