<?php

namespace Database\Seeders;

use Commerce\Core\Database\Seeders\SystemModuleSeeder;
use Commerce\Currency\Database\Seeders\CurrencySeeder;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Inventory\Database\Seeders\InventoryLocationSeeder;
use Commerce\Notification\Database\Seeders\NotificationTemplateSeeder;
use Commerce\Promotion\Database\Seeders\PromotionSeeder;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Shipping\Database\Seeders\ShippingMethodSeeder;
use Commerce\Tax\Database\Seeders\TaxRateSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IamSeeder::class,
            SystemModuleSeeder::class,
            SettingsSeeder::class,
            CurrencySeeder::class,
            ShippingMethodSeeder::class,
            TaxRateSeeder::class,
            InventoryLocationSeeder::class,
            NotificationTemplateSeeder::class,
            PromotionSeeder::class,
        ]);
    }
}
