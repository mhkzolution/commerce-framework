<?php

declare(strict_types=1);

namespace Commerce\Promotion\Database\Seeders;

use Commerce\Promotion\Models\Promotion;
use Illuminate\Database\Seeder;

final class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        Promotion::query()->updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'name' => 'Welcome 10% off',
                'type' => Promotion::TYPE_PERCENTAGE,
                'value' => 1000,
                'min_subtotal' => 2000,
                'is_active' => true,
            ],
        );

        Promotion::query()->updateOrCreate(
            ['code' => 'SAVE5'],
            [
                'name' => '$5 off',
                'type' => Promotion::TYPE_FIXED,
                'value' => 500,
                'min_subtotal' => 1000,
                'is_active' => true,
            ],
        );
    }
}
