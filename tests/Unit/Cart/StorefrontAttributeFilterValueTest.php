<?php

declare(strict_types=1);

namespace Tests\Unit\Cart;

use Commerce\Cart\Support\StorefrontAttributeFilterValue;
use PHPUnit\Framework\TestCase;

final class StorefrontAttributeFilterValueTest extends TestCase
{
    public function test_parts_expands_json_multiselect_values(): void
    {
        $parts = StorefrontAttributeFilterValue::parts('["เหลือง","แดง"]');

        $this->assertSame(['เหลือง', 'แดง'], $parts);
    }

    public function test_parts_keeps_plain_and_comma_separated_values(): void
    {
        $this->assertSame(['Red'], StorefrontAttributeFilterValue::parts('Red'));
        $this->assertSame(['S', 'M'], StorefrontAttributeFilterValue::parts('S, M'));
    }
}
