<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Commerce\Product\Support\AttributeTokenNormalizer;
use PHPUnit\Framework\TestCase;

final class AttributeTokenNormalizerTest extends TestCase
{
    private AttributeTokenNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new AttributeTokenNormalizer();
    }

    public function test_splits_ascii_and_fullwidth_commas(): void
    {
        $this->assertSame(
            ['สีฟ้า', 'สีเทา'],
            $this->normalizer->tokens('สีฟ้า, สีเทา', 'color'),
        );
        $this->assertSame(
            ['ชาย', 'หญิง'],
            $this->normalizer->tokens('ชาย，หญิง', 'gender'),
        );
    }

    public function test_size_collapses_whitespace_and_thai_units(): void
    {
        $this->assertSame(['4-5Y'], $this->normalizer->tokens('4-5 Y', 'size_top'));
        $this->assertSame(['12-18M'], $this->normalizer->tokens('12-18 เดือน', 'size_bottom'));
        $this->assertSame(['2-3Y'], $this->normalizer->tokens('2-3 ปี', 'size_bottom'));
        $this->assertSame(['5T'], $this->normalizer->tokens('5t', 'size_top'));
        $this->assertSame(['XS'], $this->normalizer->tokens('xs', 'size_top'));
        $this->assertSame(['90CM'], $this->normalizer->tokens('90cm', 'size_top'));
        $this->assertSame(['29-31'], $this->normalizer->tokens('29-31', 'size'));
    }

    public function test_age_keeps_thai_units(): void
    {
        $this->assertSame(['12เดือน'], $this->normalizer->tokens('12 เดือน', 'age'));
        $this->assertSame(['2ปี'], $this->normalizer->tokens('2 ปี', 'age'));
        $this->assertNotSame(['12M'], $this->normalizer->tokens('12เดือน', 'age'));
    }

    public function test_does_not_map_size_month_onto_age(): void
    {
        $this->assertSame(['12M'], $this->normalizer->tokens('12M', 'size_top'));
        $this->assertSame(['12เดือน'], $this->normalizer->tokens('12เดือน', 'age'));
    }

    public function test_keeps_mai_mee_and_drops_empty_tokens(): void
    {
        $this->assertSame(['ไม่มี'], $this->normalizer->tokens('ไม่มี', 'size_top'));
        $this->assertSame([], $this->normalizer->tokens(' , , ', 'color'));
    }

    public function test_dedupes_case_folded_tokens_within_one_value(): void
    {
        $this->assertSame(['Good'], $this->normalizer->tokens('Good, good', 'condition'));
    }
}
