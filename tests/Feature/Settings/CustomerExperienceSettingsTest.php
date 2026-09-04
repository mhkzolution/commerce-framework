<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\Services\CustomerExperienceConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerExperienceSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_customer_experience_settings(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.customer-experience.show'))
            ->assertOk()
            ->assertSee(__('settings::admin.customer_experience_title'), false)
            ->assertSee(__('settings::admin.cx_section_quickView'), false)
            ->assertSee(__('settings::admin.cx_section_notifications'), false)
            ->assertSee(__('settings::admin.cx_section_navigation'), false)
            ->assertSee(__('settings::admin.cx_section_productCard'), false)
            ->assertSee(__('settings::admin.cx_preview_notification'), false)
            ->assertSee('data-cx-settings', false)
            ->assertSee('data-cx-preview-root', false);
    }

    public function test_admin_can_save_customer_experience_config(): void
    {
        $payload = app(CustomerExperienceConfig::class)->defaults();
        $payload['quickView']['enabled'] = false;
        $payload['quickView']['showSku'] = true;
        $payload['notifications']['duration'] = 15;
        $payload['notifications']['position'] = 'top-left';
        $payload['navigation']['style'] = 'square';

        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.customer-experience.update'), [
                'config' => json_encode($payload),
            ])
            ->assertRedirect(route('admin.settings.customer-experience.show'));

        $saved = app(SettingQueryServiceInterface::class)->get(CustomerExperienceConfig::SETTING_KEY);

        $this->assertIsArray($saved);
        $this->assertFalse($saved['quickView']['enabled']);
        $this->assertTrue($saved['quickView']['showSku']);
        $this->assertSame(15, $saved['notifications']['duration']);
        $this->assertSame('top-left', $saved['notifications']['position']);
        $this->assertSame('square', $saved['navigation']['style']);
    }

    public function test_disabled_customer_experience_module_returns_404(): void
    {
        $module = SystemModule::query()->where('code', 'customer-experience')->firstOrFail();
        app(ModuleService::class)->updateStatus($module, ModuleStatus::Disabled);

        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.customer-experience.show'))
            ->assertNotFound();
    }
}
