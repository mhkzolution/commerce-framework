<?php

declare(strict_types=1);

namespace Tests\Feature\Currency;

use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Currency\Database\Seeders\CurrencySeeder;
use Commerce\Currency\Models\Currency;
use Commerce\Currency\Services\CurrencyQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CurrencyFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_uses_hundredths_storage_and_configured_decimal_places(): void
    {
        $this->seed(CurrencySeeder::class);
        Currency::query()->where('code', 'THB')->update(['decimal_places' => 0]);
        app(CurrencyQueryService::class)->clearCache();

        $formatted = app(CurrencyConverterInterface::class)->format(8000, 'THB');

        $this->assertSame('฿80', $formatted);
    }

    public function test_format_keeps_two_decimals_when_configured(): void
    {
        $this->seed(CurrencySeeder::class);

        $formatted = app(CurrencyConverterInterface::class)->format(8000, 'THB');

        $this->assertSame('฿80.00', $formatted);
    }
}
