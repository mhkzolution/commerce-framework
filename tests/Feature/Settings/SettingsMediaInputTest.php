<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SettingsMediaInputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_product_fallback_image_setting_renders_file_attach_on_settings_page(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Fallback product image')
            ->assertSee('data-file-attach', false)
            ->assertSee('data-attach-library-grid', false);
    }
}
