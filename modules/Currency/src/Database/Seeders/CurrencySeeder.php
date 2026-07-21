<?php

declare(strict_types=1);

namespace Commerce\Currency\Database\Seeders;

use Commerce\Currency\Models\Currency;
use Illuminate\Database\Seeder;

final class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'decimal_places' => 2,
                'rate_micro' => 1_000_000,
                'is_base' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'THB',
                'name' => 'Thai Baht',
                'symbol' => '฿',
                'decimal_places' => 2,
                'rate_micro' => 35_500_000,
                'is_base' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'decimal_places' => 2,
                'rate_micro' => 920_000,
                'is_base' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($currencies as $data) {
            Currency::query()->updateOrCreate(
                ['code' => $data['code']],
                $data,
            );
        }
    }
}
