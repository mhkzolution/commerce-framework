<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Inventory\Models\InventoryItem;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateVariantData;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\ProductVariant;
use Commerce\Product\Services\CatalogVariantRelationMigrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class CatalogVariantRelationMigratorTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_migrator_maps_json_options_without_changing_variant_uuid(): void
    {
        $setup = $this->variableProductWithColorSize();
        $product = $setup['product'];
        $redSUuid = $setup['redS']->uuid;

        $migrator = app(CatalogVariantRelationMigrator::class);
        $migrator->migrate($product);

        $this->assertSame($redSUuid, $product->fresh()->variants->firstWhere('sku', 'RED-S')?->uuid);
        $this->assertTrue(
            $product->productAttributes()->where('used_for_variations', true)->count() >= 2
        );

        $this->assertAxisMembership($product, $setup['color'], ['Red', 'Blue']);
        $this->assertAxisMembership($product, $setup['size'], ['S', 'M']);
        $this->assertVariantValue($setup['redS'], $setup['color'], $setup['red']);
        $this->assertVariantValue($setup['redS'], $setup['size'], $setup['small']);
        $this->assertVariantValue($setup['blueM'], $setup['color'], $setup['blue']);
        $this->assertVariantValue($setup['blueM'], $setup['size'], $setup['medium']);
    }

    public function test_unmatched_option_is_logged_and_skipped(): void
    {
        $setup = $this->variableProductWithColorSize();
        $product = $setup['product'];
        $attributeCount = Attribute::query()->count();

        $product->update([
            'meta' => array_merge($product->meta ?? [], [
                'variant_options' => [
                    ['id' => 'opt_color', 'name' => 'Color', 'values' => ['Red', 'Blue']],
                    ['id' => 'opt_size', 'name' => 'Size', 'values' => ['S', 'M']],
                    ['id' => 'opt_fit', 'name' => 'Fit', 'values' => ['Slim']],
                ],
            ]),
        ]);
        $setup['redS']->update([
            'meta' => ['options' => ['Color' => 'Red', 'Size' => 'S', 'Fit' => 'Slim']],
        ]);

        Log::spy();

        $migrator = app(CatalogVariantRelationMigrator::class);
        $migrator->migrate($product);

        Log::shouldHaveReceived('warning')
            ->withArgs(static function (string $message, array $context) use ($product): bool {
                return isset($context['product_id'], $context['attribute_name'], $context['option_name'])
                    && $context['product_id'] === $product->id
                    && $context['attribute_name'] === 'Fit'
                    && $context['option_name'] === 'Slim';
            })
            ->atLeast()
            ->once();

        $this->assertSame($attributeCount, Attribute::query()->count());
        $this->assertSame(0, Attribute::query()->where('name', 'Fit')->count());
        $this->assertTrue(
            $product->productAttributes()->where('used_for_variations', true)->count() >= 2
        );
    }

    public function test_rerun_does_not_duplicate_relations(): void
    {
        $setup = $this->variableProductWithColorSize();
        $product = $setup['product'];
        $migrator = app(CatalogVariantRelationMigrator::class);

        $migrator->migrate($product);

        $attributeCount = ProductAttribute::query()->where('product_id', $product->id)->count();
        $pavCount = ProductAttributeValue::query()->where('product_id', $product->id)->count();
        $valueCount = AttributeValue::query()->count();

        $this->assertGreaterThan(0, $attributeCount);
        $this->assertGreaterThan(0, $pavCount);

        $migrator->migrate($product);

        $this->assertSame($attributeCount, ProductAttribute::query()->where('product_id', $product->id)->count());
        $this->assertSame($pavCount, ProductAttributeValue::query()->where('product_id', $product->id)->count());
        $this->assertSame($valueCount, AttributeValue::query()->count());
        $this->assertSame(
            2,
            $product->productAttributes()->where('used_for_variations', true)->count(),
        );
    }

    public function test_existing_pav_text_row_is_reused_when_attribute_value_id_is_filled(): void
    {
        $setup = $this->variableProductWithColorSize();
        $product = $setup['product'];

        $variantRow = ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $setup['color']->id,
            'product_variant_id' => $setup['redS']->id,
            'attribute_value_id' => null,
            'value' => 'Red',
        ]);
        $membershipRow = ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $setup['color']->id,
            'product_variant_id' => null,
            'attribute_value_id' => null,
            'value' => 'Red',
        ]);

        $migrator = app(CatalogVariantRelationMigrator::class);
        $migrator->migrate($product);

        $this->assertSame($variantRow->id, $variantRow->fresh()->id);
        $this->assertSame($setup['red']->id, $variantRow->fresh()->attribute_value_id);
        $this->assertSame($membershipRow->id, $membershipRow->fresh()->id);
        $this->assertSame($setup['red']->id, $membershipRow->fresh()->attribute_value_id);

        $this->assertSame(
            1,
            ProductAttributeValue::query()
                ->where('product_id', $product->id)
                ->where('attribute_id', $setup['color']->id)
                ->where('product_variant_id', $setup['redS']->id)
                ->count(),
        );
        $this->assertSame(
            1,
            ProductAttributeValue::query()
                ->where('product_id', $product->id)
                ->where('attribute_id', $setup['color']->id)
                ->whereNull('product_variant_id')
                ->where('attribute_value_id', $setup['red']->id)
                ->count(),
        );
    }

    public function test_sku_and_uuid_unchanged(): void
    {
        $setup = $this->variableProductWithColorSize();
        $product = $setup['product'];
        $redS = $setup['redS'];
        $blueM = $setup['blueM'];

        $redSku = $redS->sku;
        $redUuid = $redS->uuid;
        $blueSku = $blueM->sku;
        $blueUuid = $blueM->uuid;
        $onHand = InventoryItem::query()->where('purchasable_uuid', $redUuid)->value('on_hand');

        $this->assertNotNull($onHand);

        app(CatalogVariantRelationMigrator::class)->migrate($product);

        $redS->refresh();
        $blueM->refresh();

        $this->assertSame($redSku, $redS->sku);
        $this->assertSame($redUuid, $redS->uuid);
        $this->assertSame($blueSku, $blueM->sku);
        $this->assertSame($blueUuid, $blueM->uuid);
        $this->assertSame(
            $onHand,
            InventoryItem::query()->where('purchasable_uuid', $redUuid)->value('on_hand'),
        );
        $this->assertSame(
            0,
            InventoryItem::query()->where('purchasable_uuid', $blueUuid)->count(),
        );
    }

    public function test_missing_option_label_creates_attribute_value_without_creating_attribute(): void
    {
        $setup = $this->variableProductWithColorSize(withBlueValue: false);
        $product = $setup['product'];
        $attributeCount = Attribute::query()->count();

        $this->assertNull(
            AttributeValue::query()
                ->where('attribute_id', $setup['color']->id)
                ->where('label', 'Blue')
                ->first(),
        );

        app(CatalogVariantRelationMigrator::class)->migrate($product);

        $created = AttributeValue::query()
            ->where('attribute_id', $setup['color']->id)
            ->where('label', 'Blue')
            ->first();

        $this->assertNotNull($created);
        $this->assertSame($attributeCount, Attribute::query()->count());
        $this->assertVariantValue($setup['blueM']->fresh(), $setup['color'], $created);
    }

    public function test_json_relation_migration_is_idempotent(): void
    {
        $migrationPath = base_path(
            'modules/Product/database/migrations/2026_09_07_200002_migrate_variant_json_to_relations.php',
        );
        $this->assertFileExists($migrationPath);

        $setup = $this->variableProductWithColorSize();
        $product = $setup['product'];

        $migration = require $migrationPath;
        $migration->up();

        $attributeCount = ProductAttribute::query()->where('product_id', $product->id)->count();
        $pavCount = ProductAttributeValue::query()->where('product_id', $product->id)->count();

        $this->assertGreaterThan(0, $attributeCount);
        $this->assertGreaterThan(0, $pavCount);

        $migration->up();
        $this->assertSame($attributeCount, ProductAttribute::query()->where('product_id', $product->id)->count());
        $this->assertSame($pavCount, ProductAttributeValue::query()->where('product_id', $product->id)->count());

        $migration->down();
        $migration->up();
        $this->assertSame($attributeCount, ProductAttribute::query()->where('product_id', $product->id)->count());
        $this->assertSame($pavCount, ProductAttributeValue::query()->where('product_id', $product->id)->count());
    }

    /**
     * @return array{
     *     product: Product,
     *     color: Attribute,
     *     size: Attribute,
     *     red: AttributeValue,
     *     blue: ?AttributeValue,
     *     small: AttributeValue,
     *     medium: AttributeValue,
     *     redS: ProductVariant,
     *     blueM: ProductVariant
     * }
     */
    private function variableProductWithColorSize(bool $withBlueValue = true): array
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

        $set = AttributeSet::query()->create([
            'code' => 'apparel-'.uniqid(),
            'name' => 'Apparel',
        ]);
        $set->attributes()->attach([
            $color->id => ['position' => 0, 'is_required' => false],
            $size->id => ['position' => 1, 'is_required' => false],
        ]);

        $red = $this->createAttributeValue($color, 'Red', 0);
        $blue = $withBlueValue ? $this->createAttributeValue($color, 'Blue', 1) : null;
        $small = $this->createAttributeValue($size, 'S', 0);
        $medium = $this->createAttributeValue($size, 'M', 1);

        $redS = $this->createPurchasableProduct(sku: 'RED-S');
        $product = $redS->product;
        $product->update([
            'type' => 'variable',
            'attribute_set_id' => $set->id,
            'meta' => [
                'variant_options' => [
                    ['id' => 'opt_color', 'name' => 'Color', 'values' => ['Red', 'Blue']],
                    ['id' => 'opt_size', 'name' => 'Size', 'values' => ['S', 'M']],
                ],
            ],
        ]);
        $redS->update([
            'sku' => 'RED-S',
            'meta' => ['options' => ['Color' => 'Red', 'Size' => 'S']],
        ]);

        $blueM = app(ProductServiceInterface::class)->addVariant(new CreateVariantData(
            productUuid: $product->uuid,
            sku: 'BLUE-M',
            name: 'Blue M',
            price: 2500,
            position: 1,
        ));
        $blueM->update([
            'meta' => ['options' => ['color' => 'Blue', 'size' => 'M']],
        ]);

        return [
            'product' => $product->fresh(),
            'color' => $color,
            'size' => $size,
            'red' => $red,
            'blue' => $blue,
            'small' => $small,
            'medium' => $medium,
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

    /**
     * @param  list<string>  $labels
     */
    private function assertAxisMembership(Product $product, Attribute $attribute, array $labels): void
    {
        $rows = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $attribute->id)
            ->whereNull('product_variant_id')
            ->with('attributeValue')
            ->get();

        $this->assertCount(count($labels), $rows);
        $this->assertEqualsCanonicalizing(
            $labels,
            $rows->map(static fn (ProductAttributeValue $row): string => (string) $row->attributeValue?->label)->all(),
        );
    }

    private function assertVariantValue(ProductVariant $variant, Attribute $attribute, AttributeValue $value): void
    {
        $row = ProductAttributeValue::query()
            ->where('product_id', $variant->product_id)
            ->where('attribute_id', $attribute->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        $this->assertNotNull($row);
        $this->assertSame($value->id, $row->attribute_value_id);
    }
}
