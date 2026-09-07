<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Services\ProductAttributeSetSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductAttributeSchemaTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_sync_product_attributes_from_set_creates_rows_without_values(): void
    {
        [$product, $color, $material] = $this->productWithApparelSet();

        app(ProductAttributeSetSync::class)->syncProductAttributesFromSet($product);

        $this->assertTrue(Schema::hasTable('product_attributes'));
        $this->assertSame(2, $product->productAttributes()->count());
        $this->assertSame(0, $product->attributeValues()->count());

        $rows = $product->productAttributes()->orderBy('position')->get();
        $this->assertFalse($rows[0]->used_for_variations);
        $this->assertFalse($rows[1]->used_for_variations);
        $this->assertSame($color->id, $rows[0]->attribute_id);
        $this->assertSame($material->id, $rows[1]->attribute_id);
        $this->assertSame(0, $rows[0]->position);
        $this->assertSame(1, $rows[1]->position);
    }

    public function test_two_axis_membership_rows_for_color_red_and_blue_both_persist(): void
    {
        [$product, $color] = $this->productWithApparelSet();

        $red = $this->createAttributeValue($color, 'Red', 0);
        $blue = $this->createAttributeValue($color, 'Blue', 1);

        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'product_variant_id' => null,
            'attribute_value_id' => $red->id,
            'value' => 'Red',
        ]);
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'product_variant_id' => null,
            'attribute_value_id' => $blue->id,
            'value' => 'Blue',
        ]);

        $rows = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $color->id)
            ->whereNull('product_variant_id')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(
            [$red->id, $blue->id],
            $rows->pluck('attribute_value_id')->all(),
        );
        $this->assertTrue($rows->every(static fn (ProductAttributeValue $row): bool => $row->attributeValue !== null));
    }

    public function test_sync_and_migration_are_idempotent(): void
    {
        $migrationPath = base_path(
            'modules/Product/database/migrations/2026_09_07_200001_add_product_attributes_and_value_fk.php',
        );
        $this->assertFileExists($migrationPath);

        [$product, $color] = $this->productWithApparelSet();
        $red = $this->createAttributeValue($color, 'Red', 0);
        $blue = $this->createAttributeValue($color, 'Blue', 1);

        $sync = app(ProductAttributeSetSync::class);
        $sync->syncProductAttributesFromSet($product);
        $sync->syncProductAttributesFromSet($product);

        $this->assertSame(2, ProductAttribute::query()->where('product_id', $product->id)->count());
        $this->assertSame(0, ProductAttributeValue::query()->where('product_id', $product->id)->count());

        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'product_variant_id' => null,
            'attribute_value_id' => $red->id,
            'value' => 'Red',
        ]);
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'product_variant_id' => null,
            'attribute_value_id' => $blue->id,
            'value' => 'Blue',
        ]);

        $migration = require $migrationPath;
        $migration->up();
        $migration->up();

        $this->assertSame(2, ProductAttribute::query()->where('product_id', $product->id)->count());
        $this->assertSame(2, ProductAttributeValue::query()->where('product_id', $product->id)->count());

        $migration->down();
        $this->assertFalse(Schema::hasTable('product_attributes'));
        $this->assertFalse(Schema::hasColumn('product_attribute_values', 'attribute_value_id'));

        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasTable('product_attributes'));
        $this->assertTrue(Schema::hasColumn('product_attribute_values', 'attribute_value_id'));
        $this->assertFalse(Schema::hasIndex(
            'product_attribute_values',
            'product_attribute_values_unique',
        ));
        $this->assertTrue(Schema::hasIndex(
            'product_attribute_values',
            'product_attribute_values_value_unique',
        ));
        $this->assertSame(0, ProductAttribute::query()->count());
    }

    /**
     * @return array{0: Product, 1: Attribute, 2: Attribute, 3: AttributeSet}
     */
    private function productWithApparelSet(): array
    {
        $color = Attribute::query()->create([
            'code' => 'color-'.uniqid(),
            'name' => 'Color',
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
            $material->id => ['position' => 1, 'is_required' => false],
        ]);

        $variant = $this->createPurchasableProduct();
        $product = $variant->product;
        $product->update(['attribute_set_id' => $set->id]);

        return [$product->fresh(), $color, $material, $set];
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
}
