<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_and_save_product_settings(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.products.settings.show'))
            ->assertOk()
            ->assertSee('data-file-attach', false)
            ->assertSee('Fallback image', false)
            ->assertSee('Upload', false)
            ->assertSee('From URL', false)
            ->assertSee('Library', false)
            ->assertSee('Default SKU pattern', false)
            ->assertDontSee('Fallback image media UUID', false);

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.settings.update'), [
                'sku_pattern' => '{PRODUCT}-{SIZE}',
                'fallback_image_media_uuid' => null,
            ])
            ->assertRedirect(route('admin.products.settings.show'));

        $this->assertSame('{PRODUCT}-{SIZE}', app(SettingQueryServiceInterface::class)->get('product.sku_pattern'));
    }
}
