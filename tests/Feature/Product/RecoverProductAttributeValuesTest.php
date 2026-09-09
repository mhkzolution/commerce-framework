<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Services\RecoverProductAttributeValues;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class RecoverProductAttributeValuesTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_audit_reports_size_collapse_without_writing(): void
    {
        [$product, $attributes] = $this->seedRecoveryFixture();
        $size = $attributes['Size (เสื้อ)'];
        $this->textValue($product->id, $size->id, '4-5 Y');
        $this->textValue($product->id, $size->id, '4-5Y');

        $report = app(RecoverProductAttributeValues::class)->audit();

        $sizeReport = collect($report['attributes'])->firstWhere('code', 'size_top');
        $this->assertSame(2, $sizeReport['rows']);
        $this->assertSame(['4-5Y'], $sizeReport['canonical_tokens']);
        $this->assertSame(1, $sizeReport['collapses']);
        $this->assertSame('text', $size->fresh()->type);
        $this->assertSame(0, AttributeValue::query()->count());
    }

    public function test_dry_run_does_not_write_or_create_backups(): void
    {
        [$product, $attributes] = $this->seedRecoveryFixture();
        $color = $attributes['สี'];
        $this->textValue($product->id, $color->id, 'สีฟ้า, สีเทา');

        $report = app(RecoverProductAttributeValues::class)->apply(dryRun: true);

        $this->assertTrue($report['dry_run']);
        $this->assertSame('text', $color->fresh()->type);
        $this->assertSame(0, AttributeValue::query()->count());
        $this->assertSame('สีฟ้า, สีเทา', ProductAttributeValue::query()->value('value'));
        $this->assertFalse(Schema::hasTable('_bak_product_attribute_values_attr_recovery_'.$report['suffix']));
    }

    public function test_apply_links_values_normalizes_size_and_preserves_uncovered_attributes(): void
    {
        [$product, $attributes] = $this->seedRecoveryFixture();
        $color = $attributes['สี'];
        $size = $attributes['Size (เสื้อ)'];
        $material = Attribute::query()->create([
            'code' => 'material',
            'name' => 'Material',
            'type' => 'text',
        ]);
        $this->textValue($product->id, $color->id, 'สีฟ้า, สีเทา');
        $this->textValue($product->id, $size->id, '4-5 Y');
        $materialRow = $this->textValue($product->id, $material->id, 'Cotton');

        app(RecoverProductAttributeValues::class)->apply();

        $this->assertSame('select', $color->fresh()->type);
        $this->assertSame('select', $size->fresh()->type);
        $this->assertSame(0, ProductAttributeValue::query()
            ->whereIn('attribute_id', [$color->id, $size->id])
            ->whereNull('attribute_value_id')
            ->count());
        $this->assertEqualsCanonicalizing(
            ['สีฟ้า', 'สีเทา'],
            AttributeValue::query()->where('attribute_id', $color->id)->pluck('label')->all(),
        );
        $this->assertSame(
            ['4-5Y'],
            AttributeValue::query()->where('attribute_id', $size->id)->pluck('label')->all(),
        );
        $this->assertDatabaseHas('product_attribute_values', [
            'id' => $materialRow->id,
            'value' => 'Cotton',
            'attribute_value_id' => null,
        ]);
    }

    public function test_apply_drops_covered_attribute_values_that_tokenize_to_nothing(): void
    {
        [$product, $attributes] = $this->seedRecoveryFixture();
        $color = $attributes['สี'];
        $this->textValue($product->id, $color->id, ',');

        app(RecoverProductAttributeValues::class)->apply();

        $this->assertSame(0, ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('attribute_id', $color->id)
            ->whereNull('product_variant_id')
            ->count());
        $this->assertSame('select', $color->fresh()->type);
    }

    public function test_apply_preserves_linked_values_when_the_same_attribute_has_raw_text(): void
    {
        [$product, $attributes] = $this->seedRecoveryFixture();
        $color = $attributes['สี'];
        $blue = AttributeValue::query()->create([
            'attribute_id' => $color->id,
            'code' => 'blue',
            'label' => 'Blue',
            'position' => 1,
        ]);
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'product_variant_id' => null,
            'attribute_value_id' => $blue->id,
            'value' => 'Blue',
        ]);
        $this->textValue($product->id, $color->id, 'Red');

        app(RecoverProductAttributeValues::class)->apply();

        $this->assertEqualsCanonicalizing(
            ['Blue', 'Red'],
            ProductAttributeValue::query()
                ->where('product_id', $product->id)
                ->where('attribute_id', $color->id)
                ->pluck('value')
                ->all(),
        );
    }

    public function test_apply_returns_the_numbered_suffix_of_the_backup_created_this_run(): void
    {
        $this->seedRecoveryFixture();
        $baseSuffix = now()->format('Ymd');
        Schema::create('_bak_product_attribute_values_attr_recovery_'.$baseSuffix, function (Blueprint $table): void {
            $table->unsignedBigInteger('id');
        });

        $result = app(RecoverProductAttributeValues::class)->apply(force: true);

        $this->assertSame($baseSuffix.'_1', $result['suffix']);
        $this->assertTrue(Schema::hasTable(
            '_bak_product_attribute_values_attr_recovery_'.$result['suffix'],
        ));
        $this->assertTrue(Schema::hasTable('_bak_attr_recovery_meta_'.$result['suffix']));
        $this->assertSame(
            $result['suffix'],
            app(RecoverProductAttributeValues::class)->apply()['suffix'],
        );
    }

    public function test_apply_fails_before_writing_backups_when_attribute_set_is_missing(): void
    {
        [, , $set] = $this->seedRecoveryFixture();
        $set->delete();
        $suffix = now()->format('Ymd');

        try {
            app(RecoverProductAttributeValues::class)->apply();
            $this->fail('Expected recovery to reject a missing attribute set.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('woocommerce_default', $exception->getMessage());
        }

        $this->assertFalse(Schema::hasTable(
            '_bak_product_attribute_values_attr_recovery_'.$suffix,
        ));
    }

    public function test_apply_attaches_language_and_shoe_size_and_set_syncs_products(): void
    {
        [$product, $attributes, $set] = $this->seedRecoveryFixture();

        app(RecoverProductAttributeValues::class)->apply();

        $attachedIds = $set->fresh()->attributes()->pluck('attributes.id')->all();
        $this->assertContains($attributes['ภาษา']->id, $attachedIds);
        $this->assertContains($attributes['Size (รองเท้า)']->id, $attachedIds);
        foreach ($attachedIds as $attributeId) {
            $this->assertDatabaseHas('product_attributes', [
                'product_id' => $product->id,
                'attribute_id' => $attributeId,
                'used_for_variations' => false,
            ]);
        }
    }

    public function test_second_apply_is_idempotent(): void
    {
        [$product, $attributes] = $this->seedRecoveryFixture();
        $this->textValue($product->id, $attributes['สี']->id, 'สีฟ้า, สีเทา');
        $recovery = app(RecoverProductAttributeValues::class);
        $first = $recovery->apply();
        $pavCount = ProductAttributeValue::query()->count();
        $valueCount = AttributeValue::query()->count();

        $second = $recovery->apply();

        $this->assertTrue($second['already_applied']);
        $this->assertSame($first['suffix'], $second['suffix']);
        $this->assertSame($pavCount, ProductAttributeValue::query()->count());
        $this->assertSame($valueCount, AttributeValue::query()->count());
    }

    public function test_restore_reverts_values_types_set_membership_and_product_attributes(): void
    {
        [$product, $attributes, $set] = $this->seedRecoveryFixture();
        $color = $attributes['สี'];
        $this->textValue($product->id, $color->id, 'สีฟ้า');
        $beforeProductAttributes = ProductAttribute::query()->count();
        $suffix = app(RecoverProductAttributeValues::class)->apply()['suffix'];

        app(RecoverProductAttributeValues::class)->restore($suffix);

        $this->assertSame('text', $color->fresh()->type);
        $this->assertSame(0, AttributeValue::query()->count());
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'attribute_value_id' => null,
            'value' => 'สีฟ้า',
        ]);
        $this->assertFalse($set->fresh()->attributes()->whereIn('attributes.id', [
            $attributes['ภาษา']->id,
            $attributes['Size (รองเท้า)']->id,
        ])->exists());
        $this->assertSame($beforeProductAttributes, ProductAttribute::query()->count());
    }

    public function test_commands_are_registered_and_audit_and_dry_run_do_not_write(): void
    {
        [$product, $attributes] = $this->seedRecoveryFixture();
        $this->textValue($product->id, $attributes['Size (เสื้อ)']->id, '4-5 Y');

        $this->artisan('product:recover-attribute-values', ['--audit' => true])
            ->assertSuccessful();
        $this->artisan('product:recover-attribute-values', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame('text', $attributes['Size (เสื้อ)']->fresh()->type);
        $this->assertSame(0, AttributeValue::query()->count());
    }

    /**
     * @return array{0: Product, 1: array<string, Attribute>, 2: AttributeSet}
     */
    private function seedRecoveryFixture(): array
    {
        $attributes = [];
        foreach ([
            'สี' => 'color',
            'เพศ' => 'gender',
            'Size (เสื้อ)' => 'size_top',
            'Size (กางเกง)' => 'size_bottom',
            'อายุ' => 'age',
            'สภาพ' => 'condition',
            'ภาษา' => 'attr_0ebb640bb9a7',
            'Size (รองเท้า)' => 'size',
        ] as $name => $code) {
            $attributes[$name] = Attribute::query()->create([
                'code' => $code,
                'name' => $name,
                'type' => 'text',
                'is_filterable' => true,
                'is_visible' => true,
            ]);
        }

        $set = AttributeSet::query()->create([
            'code' => 'woocommerce_default',
            'name' => 'WooCommerce Default',
        ]);
        $set->attributes()->attach($attributes['สี']->id, [
            'position' => 0,
            'is_required' => false,
        ]);

        $product = $this->createPurchasableProduct(sku: 'RECOVERY-001')->product;
        $product->update(['attribute_set_id' => $set->id]);

        return [$product, $attributes, $set];
    }

    private function textValue(int $productId, int $attributeId, string $value): ProductAttributeValue
    {
        return ProductAttributeValue::query()->create([
            'product_id' => $productId,
            'attribute_id' => $attributeId,
            'product_variant_id' => null,
            'attribute_value_id' => null,
            'value' => $value,
        ]);
    }
}
