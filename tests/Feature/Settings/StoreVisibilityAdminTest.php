<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Storefront\StoreVisibility;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\Services\StoreVisibilityConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsStoreVisibility;
use Tests\TestCase;

final class StoreVisibilityAdminTest extends TestCase
{
    use RefreshDatabase;
    use SetsStoreVisibility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_store_visibility_settings(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.store-visibility.show'))
            ->assertOk()
            ->assertSee(__('settings::admin.store_visibility_title'), false)
            ->assertSee(__('settings::admin.store_visibility_mode_public'), false)
            ->assertSee(__('settings::admin.store_visibility_mode_catalog'), false)
            ->assertSee(__('settings::admin.store_visibility_mode_members'), false)
            ->assertSee(__('settings::admin.store_visibility_mode_private'), false);
    }

    public function test_admin_can_save_catalog_visibility(): void
    {
        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.store-visibility.update'), [
                'visibility' => StoreVisibility::Catalog->value,
            ])
            ->assertRedirect(route('admin.settings.store-visibility.show'));

        $this->assertSame(
            StoreVisibility::Catalog->value,
            app(SettingQueryServiceInterface::class)->get(StoreVisibilityConfig::SETTING_KEY),
        );
        $this->assertSame(StoreVisibility::Catalog, app(StoreVisibilityConfig::class)->mode());
    }

    public function test_invalid_visibility_is_rejected(): void
    {
        app(StoreVisibilityConfig::class)->ensureRegistered();

        $this->actingAs(User::query()->first())
            ->from(route('admin.settings.store-visibility.show'))
            ->put(route('admin.settings.store-visibility.update'), [
                'visibility' => 'wholesale',
            ])
            ->assertRedirect(route('admin.settings.store-visibility.show'))
            ->assertSessionHasErrors('visibility');
    }
}
