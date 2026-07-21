<?php

declare(strict_types=1);

namespace Commerce\Tax\Database\Seeders;

use Commerce\Tax\Models\TaxRate;
use Illuminate\Database\Seeder;

final class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        TaxRate::query()->updateOrCreate(
            ['code' => 'us-standard'],
            [
                'name' => 'US Sales Tax',
                'rate_bps' => 700,
                'country_code' => 'US',
                'is_active' => true,
                'priority' => 10,
            ],
        );
    }
}
