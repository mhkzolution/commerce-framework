<?php

namespace Database\Seeders;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IamSeeder::class,
            \Commerce\Settings\Database\Seeders\SettingsSeeder::class,
            \Commerce\Currency\Database\Seeders\CurrencySeeder::class,
            \Commerce\Shipping\Database\Seeders\ShippingMethodSeeder::class,
            \Commerce\Tax\Database\Seeders\TaxRateSeeder::class,
            \Commerce\Inventory\Database\Seeders\InventoryLocationSeeder::class,
            \Commerce\Notification\Database\Seeders\NotificationTemplateSeeder::class,
            \Commerce\Promotion\Database\Seeders\PromotionSeeder::class,
        ]);
    }
}
