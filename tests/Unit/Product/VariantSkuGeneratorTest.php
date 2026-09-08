<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Commerce\Product\Services\VariantSkuGenerator;
use PHPUnit\Framework\TestCase;

final class VariantSkuGeneratorTest extends TestCase
{
    public function test_blank_sku_uses_prefix_and_options(): void
    {
        $generator = new VariantSkuGenerator(fn (): array => ['TSHIRT-RED-S']);

        $generated = $generator->allocate('TSHIRT', ['Red', 'S'], null);

        $this->assertSame('TSHIRT-RED-S-2', $generated->sku);
        $this->assertTrue($generated->isAuto);
    }

    public function test_typed_sku_is_not_auto(): void
    {
        $generated = (new VariantSkuGenerator(fn (): array => []))
            ->allocate('TSHIRT', ['Red'], 'CUSTOM-1');

        $this->assertSame('CUSTOM-1', $generated->sku);
        $this->assertFalse($generated->isAuto);
    }

    public function test_generated_sku_slugifies_each_part(): void
    {
        $generated = (new VariantSkuGenerator(fn (): array => []))
            ->allocate(' Summer Shirt ', ['Dark Red', 'Size: L'], null);

        $this->assertSame('SUMMER-SHIRT-DARK-RED-SIZE-L', $generated->sku);
        $this->assertTrue($generated->isAuto);
    }

    public function test_collisions_increment_from_two(): void
    {
        $generator = new VariantSkuGenerator(
            fn (): array => ['TSHIRT-RED', 'TSHIRT-RED-2'],
        );

        $generated = $generator->allocate('TSHIRT', ['Red'], null);

        $this->assertSame('TSHIRT-RED-3', $generated->sku);
    }

    public function test_empty_prefix_falls_back_to_sku(): void
    {
        $generated = (new VariantSkuGenerator(fn (): array => []))
            ->allocate(' --- ', [], '   ');

        $this->assertSame('SKU', $generated->sku);
        $this->assertTrue($generated->isAuto);
    }

    public function test_zero_option_value_is_preserved(): void
    {
        $generated = (new VariantSkuGenerator(fn (): array => []))
            ->allocate('PRODUCT', ['0'], null);

        $this->assertSame('PRODUCT-0', $generated->sku);
    }
}
