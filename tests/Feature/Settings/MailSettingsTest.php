<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\Support\MailConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_mail_settings_page(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.mail.show'))
            ->assertOk()
            ->assertSee(__('settings::admin.mail_title'), false)
            ->assertSee(__('settings::admin.mail_transport'), false);
    }

    public function test_admin_can_save_mail_settings(): void
    {
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
        $this->assertSame('587', $settings->get('mail.port'));
        $this->assertSame('mailer@example.com', $settings->get('mail.username'));
        $this->assertSame('secret-pass', $settings->get('mail.password'));
        $this->assertSame('tls', $settings->get('mail.encryption'));
        $this->assertSame('noreply@example.com', $settings->get('mail.from_address'));
        $this->assertSame('Example Shop', $settings->get('mail.from_name'));
    }

    public function test_mail_configurator_applies_saved_settings(): void
    {
        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.mail.update'), [
                'mailer' => 'smtp',
                'host' => 'smtp.saved.test',
                'port' => 2525,
                'username' => 'saved-user',
                'password' => 'saved-pass',
                'encryption' => 'tls',
                'from_address' => 'saved@example.com',
                'from_name' => 'Saved Sender',
            ]);

        Config::set('mail.default', 'log');
        MailConfigurator::apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.saved.test', config('mail.mailers.smtp.host'));
        $this->assertSame(2525, config('mail.mailers.smtp.port'));
        $this->assertSame('saved-user', config('mail.mailers.smtp.username'));
        $this->assertSame('saved-pass', config('mail.mailers.smtp.password'));
        $this->assertSame('saved@example.com', config('mail.from.address'));
        $this->assertSame('Saved Sender', config('mail.from.name'));
    }
}
