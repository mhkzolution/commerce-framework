<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\Support\ThemeDesignTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AppearanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_appearance_settings(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.appearance.show'))
            ->assertOk()
            ->assertSee(__('settings::admin.appearance_title'), false);
    }

    public function test_admin_can_save_theme_colors(): void
    {
        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.appearance.update'), [
                'primary' => '#111827',
                'primary_hover' => '#0f172a',
                'primary_active' => '#020617',
                'accent' => '#db2777',
                'accent_hover' => '#be185d',
                'background' => '#f8fafc',
                'surface' => '#ffffff',
            ])
            ->assertRedirect(route('admin.settings.appearance.show'));

        $this->assertSame('#111827', app(SettingQueryServiceInterface::class)->get('theme.primary'));
        $this->assertSame('#111827', ThemeDesignTokens::resolve()['primary'] ?? null);
    }
}
