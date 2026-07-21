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
        ]);
    }
}
