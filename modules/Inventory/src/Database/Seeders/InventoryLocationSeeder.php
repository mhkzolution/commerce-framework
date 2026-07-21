<?php

declare(strict_types=1);

namespace Commerce\Inventory\Database\Seeders;

use Commerce\Inventory\Models\InventoryLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class InventoryLocationSeeder extends Seeder
{
    public function run(): void
    {
        InventoryLocation::query()->updateOrCreate(
            ['code' => 'default'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Default Warehouse',
                'is_default' => true,
                'is_active' => true,
            ],
        );
    }
}
