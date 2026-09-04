<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\Support\AuthConfigurator;
use Commerce\Settings\Support\MailConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class MailAndAuthSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_and_save_mail_settings(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.mail.show'))
            ->assertOk()
            ->assertSee(__('settings::admin.mail_title'), false)
            ->assertSee(__('settings::admin.mail_transport'), false);

        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.mail.update'), [
                'mailer' => 'smtp',
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'mailer@example.com',
                'password' => 'secret-pass',
                'encryption' => 'tls',
                'from_address' => 'noreply@example.com',
                'from_name' => 'Example Shop',
            ])
            ->assertRedirect(route('admin.settings.mail.show'));

        $settings = app(SettingQueryServiceInterface::class);
        $this->assertSame('smtp', $settings->get('mail.mailer'));
        $this->assertSame('smtp.example.com', $settings->get('mail.host'));

        Config::set('mail.default', 'log');
        MailConfigurator::apply();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
    }

    public function test_admin_can_view_and_save_auth_settings(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.auth.show'))
            ->assertOk()
            ->assertSee(__('settings::admin.auth_title'))
            ->assertSee(__('settings::admin.auth_line'));

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
        $this->assertFalse(config('customers.storefront.registration.enabled'));
    }

    public function test_admin_can_view_translation_index(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.translations.index', ['locale' => 'th']))
            ->assertOk()
            ->assertSee(__('settings::admin.translations_title'), false);
    }
}
