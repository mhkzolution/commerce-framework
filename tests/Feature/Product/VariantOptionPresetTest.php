<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Catalog\Models\Attribute;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Product\Database\Seeders\VariantOptionPresetSeeder;
use Commerce\Product\Services\VariantOptionPresetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class VariantOptionPresetTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_view_variant_option_create_form(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.catalog.variant-options.create'))
            ->assertOk()
            ->assertSee(__('product::workspace.variant_option_name'), false);
    }

    public function test_admin_can_create_variant_option_preset(): void
    {
        $this->actingAs(User::query()->first())
            ->post(route('admin.catalog.variant-options.store'), [
                'name' => 'สี',
                'code' => 'color_test',
                'position' => 0,
                'options' => ['ดำ', 'ขาว'],
            ])
            ->assertRedirect(route('admin.catalog.variant-options.index'));

        $this->assertDatabaseHas('attributes', [
            'code' => 'color_test',
            'name' => 'สี',
        ]);
    }

    public function test_variant_option_presets_appear_on_product_create_page(): void
    {
        app(VariantOptionPresetService::class)->create(
            name: 'ไซส์ (เสื้อ)',
            code: 'size_top_test',
            options: ['S', 'M', 'L'],
        );

        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.products.create'));

        $response->assertOk();
        $response->assertSee('ไซส์ (เสื้อ)', false);
        $response->assertSee('"optionPresets"', false);
        $response->assertSee('S', false);
    }

    public function test_admin_can_update_and_delete_variant_option_preset(): void
    {
        $attribute = app(VariantOptionPresetService::class)->create(
            name: 'วัสดุ',
            code: 'material_test',
            options: ['ผ้าฝ้าย'],
        );

        $this->actingAs(User::query()->first())
            ->put(route('admin.catalog.variant-options.update', $attribute), [
                'name' => 'วัสดุผ้า',
                'code' => 'material_test',
                'position' => 1,
                'options' => ['ผ้าฝ้าย', 'โพลีเอสเตอร์'],
            ])
            ->assertRedirect(route('admin.catalog.variant-options.index'));

        $attribute->refresh();
        $this->assertSame('วัสดุผ้า', $attribute->name);
        $this->assertSame(['ผ้าฝ้าย', 'โพลีเอสเตอร์'], $attribute->options);

        $this->actingAs(User::query()->first())
            ->delete(route('admin.catalog.variant-options.destroy', $attribute))
            ->assertRedirect(route('admin.catalog.variant-options.index'));

        $this->assertNull(Attribute::query()->where('uuid', $attribute->uuid)->first());
    }

    public function test_seeder_creates_default_variant_presets(): void
    {
        $this->seed(VariantOptionPresetSeeder::class);

        $map = app(VariantOptionPresetService::class)->presetMap();

        $this->assertArrayHasKey('สี', $map);
        $this->assertArrayHasKey('ไซส์ (เสื้อ)', $map);
        $this->assertContains('M', $map['ไซส์ (เสื้อ)']);
    }
}
