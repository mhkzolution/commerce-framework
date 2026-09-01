<?php

declare(strict_types=1);

namespace Commerce\Product\Database\Seeders;

use Commerce\Product\Services\VariantOptionPresetService;
use Illuminate\Database\Seeder;

/**
 * ตัวเลือก Variant สำเร็จรูป (สี, ไซส์เสื้อ, ไซส์รองเท้า) สำหรับ Product Workspace
 */
final class VariantOptionPresetSeeder extends Seeder
{
    public function run(VariantOptionPresetService $presetService): void
    {
        $presets = [
            [
                'name' => 'สี',
                'code' => 'color',
                'position' => 0,
                'options' => ['ดำ', 'ขาว', 'น้ำเงิน', 'แดง', 'เทา', 'ครีม'],
            ],
            [
                'name' => 'ไซส์ (เสื้อ)',
                'code' => 'size_top',
                'position' => 1,
                'options' => ['XS', 'S', 'M', 'L', 'XL', '2XL'],
            ],
            [
                'name' => 'ไซส์ (กางเกง)',
                'code' => 'size_bottom',
                'position' => 2,
                'options' => ['28', '30', '32', '34', '36'],
            ],
            [
                'name' => 'ไซส์ (รองเท้า)',
                'code' => 'size_shoe',
                'position' => 3,
                'options' => ['38', '39', '40', '41', '42', '43', '44'],
            ],
        ];

        foreach ($presets as $preset) {
            $exists = $presetService->allOrdered()
                ->contains(static fn ($attribute): bool => $attribute->code === $preset['code']);

            if ($exists) {
                continue;
            }

            $presetService->create(
                name: $preset['name'],
                code: $preset['code'],
                options: $preset['options'],
                position: $preset['position'],
            );
        }
    }
}
