<?php

declare(strict_types=1);

namespace Tests\Feature\Currency;

use Commerce\Currency\Database\Seeders\CurrencySeeder;
use Commerce\Currency\Models\Currency;
use Commerce\Currency\Services\CurrencyQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ThbBaseCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_defaults_use_thb(): void
    {
        $this->assertSame('THB', config('cart.default_currency'));
        $this->assertSame('THB', config('orders.default_currency'));
        $this->assertSame('THB', config('shipping.default_currency'));
    }

    public function test_currency_seeder_marks_thb_as_the_base(): void
    {
        $this->seed(CurrencySeeder::class);

        $thb = Currency::query()->where('code', 'THB')->first();
        $usd = Currency::query()->where('code', 'USD')->first();

        $this->assertNotNull($thb);
        $this->assertTrue($thb->is_base);
        $this->assertSame(1_000_000, (int) $thb->rate_micro);
        $this->assertNotNull($usd);
        $this->assertFalse($usd->is_base);
        $this->assertSame('THB', app(CurrencyQueryService::class)->baseCurrency()?->normalizedCode());
    }
}
