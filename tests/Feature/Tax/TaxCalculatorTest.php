<?php

declare(strict_types=1);

namespace Tests\Feature\Tax;

use Commerce\Contracts\Tax\TaxCalculatorInterface;
use Commerce\Tax\Database\Seeders\TaxRateSeeder;
use Commerce\Tax\DTO\TaxContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TaxCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TaxRateSeeder::class);
    }

    public function test_tax_calculator_returns_lines_for_us_address(): void
    {
        $calculator = app(TaxCalculatorInterface::class);

        $lines = $calculator->calculate(new TaxContext(
            currency: 'USD',
            lineItems: [['line_total' => 10000]],
            shippingAddress: ['country_code' => 'US'],
        ));

        $this->assertNotEmpty($lines);
        $this->assertGreaterThan(0, array_sum(array_map(static fn ($line) => $line->getAmount(), $lines)));
    }
}
