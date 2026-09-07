<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Commerce\Product\Services\VariantIdentity;
use PHPUnit\Framework\TestCase;

final class VariantIdentityTest extends TestCase
{
    public function test_key_is_order_independent(): void
    {
        $this->assertSame(
            VariantIdentity::key([5, 1]),
            VariantIdentity::key([1, 5]),
        );
        $this->assertSame('1-5', VariantIdentity::key([5, 1]));
    }
}
