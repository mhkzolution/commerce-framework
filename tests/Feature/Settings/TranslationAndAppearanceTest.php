<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\Services\TranslationCatalogService;
use Commerce\Settings\Support\ThemeDesignTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TranslationAndAppearanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_translation_index(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.translations.index', ['locale' => 'th']))
            ->assertOk()
            ->assertSee(__('settings::admin.translations_title'), false);
    }

    public function test_admin_can_edit_and_save_translation_file(): void
    {
        $catalog = app(TranslationCatalogService::class);
        $original = $catalog->load('product', 'workspace', 'th');

        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.translations.update', ['namespace' => 'product', 'file' => 'workspace']), [
                'locale' => 'th',
                'translations' => array_merge($original, [
                    'new_product' => 'สินค้าใหม่ทดสอบ',
                ]),
            ])
            ->assertRedirect(route('admin.settings.translations.edit', [
                'namespace' => 'product',
                'file' => 'workspace',
                'locale' => 'th',
            ]));

        $this->assertSame('สินค้าใหม่ทดสอบ', $catalog->load('product', 'workspace', 'th')['new_product']);

        $catalog->save('product', 'workspace', 'th', $original);
    }

    public function test_admin_can_view_and_save_theme_colors(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.appearance.show'))
            ->assertOk()
            ->assertSee(__('settings::admin.appearance_title'), false);

        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.appearance.update'), [
                'primary' => '#0ea5e9',
                'primary_hover' => '#0284c7',
                'primary_active' => '#0369a1',
                'background' => '#f8fafc',
                'surface' => '#ffffff',
                'accent' => '#0ea5e9',
                'accent_hover' => '#0284c7',
            ])
            ->assertRedirect(route('admin.settings.appearance.show'));

        $settings = app(SettingQueryServiceInterface::class);
        $this->assertSame('#0ea5e9', $settings->get('theme.primary'));

        $overrides = ThemeDesignTokens::resolve();
        $this->assertSame('#0ea5e9', $overrides['primary']);
        $this->assertSame('#0284c7', $overrides['accent-hover']);
    }

    public function test_storefront_includes_theme_color_overrides(): void
    {
        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.appearance.update'), [
                'primary' => '#be123c',
                'primary_hover' => '#9f1239',
                'primary_active' => '#881337',
                'background' => '#fff1f2',
                'surface' => '#ffffff',
                'accent' => '#be123c',
                'accent_hover' => '#9f1239',
            ]);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('--color-primary: #be123c', false)
            ->assertSee('--color-primary-hover: #9f1239', false)
            ->assertSee('--color-accent-hover: #9f1239', false)
            ->assertSee('--color-background: #fff1f2', false)
            ->assertSee('--color-link-hover: var(--color-primary-hover', false);
    }
}
