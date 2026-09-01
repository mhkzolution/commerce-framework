<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\Support\AuthConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class AuthSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_auth_settings_page(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.auth.show'))
            ->assertOk()
            ->assertSee(__('settings::admin.auth_title'), false)
            ->assertSee(__('settings::admin.auth_line'), false)
            ->assertSee(__('settings::admin.auth_recaptcha'), false);
    }

    public function test_admin_can_save_auth_settings_and_apply_to_storefront(): void
    {
        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.auth.update'), [
                'line_enabled' => true,
                'line_channel_id' => 'line-channel-id',
                'line_channel_secret' => 'line-channel-secret',
                'recaptcha_enabled' => true,
                'recaptcha_site_key' => 'site-key',
                'recaptcha_secret_key' => 'secret-key',
                'recaptcha_min_score' => '0.6',
                'registration_enabled' => false,
            ])
            ->assertRedirect(route('admin.settings.auth.show'));

        Config::set('customers.storefront.oauth.line.enabled', false);
        AuthConfigurator::apply();

        $this->assertTrue(config('customers.storefront.oauth.line.enabled'));
        $this->assertSame('line-channel-id', config('customers.storefront.oauth.line.channel_id'));
        $this->assertTrue(config('customers.storefront.recaptcha.enabled'));
        $this->assertFalse(config('customers.storefront.registration.enabled'));

        $this->get(route('storefront.account.login'))
            ->assertOk()
            ->assertSee(__('customers::auth.oauth_line'), false);
    }
}
