<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Services\ProductAttributeValueLinker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductAttributeValueLinkerTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_splits_color_and_sets_attribute_value_id(): void
    {
        $product = $this->createPurchasableProduct(sku: 'LNK-TEE')->product;
        $color = Attribute::query()->create([
            'code' => 'color',
            'name' => 'สี',
            'type' => 'text',
            'is_filterable' => true,
            'is_visible' => true,
        ]);

        app(ProductAttributeValueLinker::class)->syncProductLevel($product, [
            $color->id => 'สีฟ้า, สีเทา',
        ]);

        $rows = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $color->id)
            ->whereNull('product_variant_id')
            ->with('attributeValue')
            ->get();

        $this->assertCount(2, $rows);
        $this->assertTrue($rows->every(fn ($row) => $row->attribute_value_id !== null));
        $this->assertEqualsCanonicalizing(
            ['สีฟ้า', 'สีเทา'],
            $rows->pluck('attributeValue.label')->all(),
        );
    }

    public function test_size_top_canonicalizes_before_insert(): void
    {
        $product = $this->createPurchasableProduct(sku: 'LNK-SIZE')->product;
        $size = Attribute::query()->create([
            'code' => 'size_top',
            'name' => 'Size (เสื้อ)',
            'type' => 'text',
            'is_filterable' => true,
            'is_visible' => true,
        ]);

        app(ProductAttributeValueLinker::class)->syncProductLevel($product, [
            $size->id => '4-5 Y',
        ]);

        $row = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $size->id)
            ->first();

        $this->assertNotNull($row?->attribute_value_id);
        $this->assertSame('4-5Y', $row->attributeValue?->label);
    }

    public function test_second_sync_is_idempotent(): void
    {
        $product = $this->createPurchasableProduct(sku: 'LNK-IDEM')->product;
        $color = Attribute::query()->create([
            'code' => 'color',
            'name' => 'สี',
            'type' => 'text',
            'is_filterable' => true,
            'is_visible' => true,
        ]);
        $linker = app(ProductAttributeValueLinker::class);
        $linker->syncProductLevel($product, [$color->id => 'สีฟ้า']);
        $linker->syncProductLevel($product, [$color->id => 'สีฟ้า']);

        $this->assertSame(1, ProductAttributeValue::query()->where('product_id', $product->id)->count());
        $this->assertSame(1, AttributeValue::query()->where('attribute_id', $color->id)->count());
    }

    public function test_sync_collapses_duplicate_product_level_rows(): void
    {
        $product = $this->createPurchasableProduct(sku: 'LNK-DUP')->product;
        $color = Attribute::query()->create([
            'code' => 'color',
            'name' => 'สี',
            'type' => 'text',
            'is_filterable' => true,
            'is_visible' => true,
        ]);
        $blue = AttributeValue::query()->create([
            'attribute_id' => $color->id,
            'code' => 'blue',
            'label' => 'สีฟ้า',
            'position' => 1,
        ]);
        $row = [
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'product_variant_id' => null,
            'attribute_value_id' => $blue->id,
            'value' => $blue->label,
        ];
        ProductAttributeValue::query()->create($row);
        ProductAttributeValue::query()->create($row);

        app(ProductAttributeValueLinker::class)->syncProductLevel($product, [
            $color->id => $blue->label,
        ]);

        $this->assertSame(1, ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $color->id)
            ->where('attribute_value_id', $blue->id)
            ->whereNull('product_variant_id')
            ->count());
    }

    public function test_replace_removes_product_level_attributes_missing_from_payload(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'LNK-REPLACE');
        $product = $variant->product;
        $color = Attribute::query()->create([
            'code' => 'color',
            'name' => 'สี',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
        ]);
        $size = Attribute::query()->create([
            'code' => 'size_top',
            'name' => 'Size (เสื้อ)',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
        ]);
        $linker = app(ProductAttributeValueLinker::class);
        $linker->syncProductLevel($product, [
            $color->id => 'สีฟ้า',
            $size->id => '4-5 Y',
        ]);

        $blue = AttributeValue::query()
            ->where('attribute_id', $color->id)
            ->where('label', 'สีฟ้า')
            ->firstOrFail();
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'product_variant_id' => $variant->id,
            'attribute_value_id' => $blue->id,
            'value' => $blue->label,
        ]);

        $linker->syncProductLevel(
            $product,
            [$color->id => 'สีฟ้า'],
            replaceUnusedAttributes: true,
        );

        $this->assertSame(
            [$color->id],
            ProductAttributeValue::query()
                ->where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->pluck('attribute_id')
                ->all(),
        );
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'product_variant_id' => $variant->id,
            'attribute_value_id' => $blue->id,
        ]);
    }

    public function test_replace_with_empty_payload_removes_all_product_level_attributes(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'LNK-EMPTY');
        $product = $variant->product;
        $color = Attribute::query()->create([
            'code' => 'color',
            'name' => 'สี',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
        ]);
        $linker = app(ProductAttributeValueLinker::class);
        $linker->syncProductLevel($product, [$color->id => 'สีฟ้า']);

        $blue = AttributeValue::query()
            ->where('attribute_id', $color->id)
            ->firstOrFail();
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'product_variant_id' => $variant->id,
            'attribute_value_id' => $blue->id,
            'value' => $blue->label,
        ]);

        $linker->syncProductLevel($product, [], replaceUnusedAttributes: true);

        $this->assertSame(0, ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->count());
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'attribute_value_id' => $blue->id,
        ]);
    }
}
