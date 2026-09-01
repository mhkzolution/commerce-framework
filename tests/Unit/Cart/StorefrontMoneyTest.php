<?php

declare(strict_types=1);

namespace Tests\Unit\Cart;

use Commerce\Cart\Support\StorefrontMoney;
use Commerce\Currency\Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontMoneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CurrencySeeder::class);
    }

    public function test_formats_minor_amounts_with_symbol_before_amount(): void
    {
        $this->assertSame('฿ 1,234.56', StorefrontMoney::formatMinor(123456, 'THB'));
    }

    public function test_formats_major_amounts_with_custom_decimals(): void
    {
        $this->assertSame('฿ 1,000', StorefrontMoney::formatMajor(1000, 'THB', 0));
    }

    public function test_exposes_js_payload_for_storefront_scripts(): void
    {
        $payload = StorefrontMoney::jsPayload('THB');

        $this->assertSame('THB', $payload['currency']);
        $this->assertSame('฿', $payload['symbol']);
        $this->assertSame(2, $payload['decimals']);
    }
}
