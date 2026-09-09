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
}
