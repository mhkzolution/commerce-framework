<?php

declare(strict_types=1);

namespace Commerce\Shipping\Database\Seeders;

use Commerce\Shipping\Models\ShippingMethod;
use Illuminate\Database\Seeder;

final class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'code' => 'standard',
                'name' => 'Standard Shipping',
                'description' => 'Delivery in 5-7 business days',
                'price' => 500,
                'sort_order' => 10,
            ],
            [
                'code' => 'express',
                'name' => 'Express Shipping',
                'description' => 'Delivery in 2-3 business days',
                'price' => 1500,
                'sort_order' => 20,
            ],
            [
                'code' => 'free',
                'name' => 'Free Shipping',
                'description' => 'Free on orders over $50',
                'price' => 0,
                'min_subtotal' => 5000,
                'sort_order' => 5,
            ],
        ];

        foreach ($methods as $method) {
            ShippingMethod::query()->updateOrCreate(
                ['code' => $method['code']],
                array_merge($method, ['is_active' => true]),
            );
        }
    }
}
