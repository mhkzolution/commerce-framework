<?php

declare(strict_types=1);

namespace Tests\Feature\Iam;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Media\Models\Media;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminLoginPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_login_page_renders_compact_admin_card_without_oauth(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('data-admin-auth', false)
            ->assertSee(__('iam::auth.sign_in'), false)
            ->assertSee(__('iam::auth.email'), false)
            ->assertSee(__('iam::auth.password'), false)
            ->assertSee(__('iam::auth.remember'), false)
            ->assertSee(__('iam::auth.forgot_password'), false)
            ->assertSee('cf-btn--primary', false)
            ->assertDontSee('Or continue with', false)
            ->assertDontSee(route('admin.login.oauth.redirect', 'google'), false);
    }

    public function test_login_page_shows_configured_site_name_and_logo(): void
    {
        $logo = Media::query()->create([
            'filename' => 'admin-logo.png',
            'original_filename' => 'admin-logo.png',
            'mime_type' => 'image/png',
            'media_type' => 'image',
            'size' => 1024,
            'disk' => 'public',
            'path' => 'media/admin-logo.png',
        ]);

        app(SettingServiceInterface::class)->updateGroup(new UpdateSettingsGroupData(
            group: 'store',
            values: [
                'name' => 'Punpun Admin',
                'logo_media_uuid' => $logo->uuid,
            ],
        ));

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Punpun Admin', false)
            ->assertSee('admin-auth-brand__logo', false)
            ->assertSee('/storage/media/admin-logo.png', false);
    }

    public function test_login_page_hides_logo_when_none_is_configured(): void
    {
        app(SettingServiceInterface::class)->updateGroup(new UpdateSettingsGroupData(
            group: 'store',
            values: [
                'name' => 'Harbor Shop',
                'logo_media_uuid' => null,
            ],
        ));

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Harbor Shop', false)
            ->assertDontSee('admin-auth-brand__logo', false);
    }

    public function test_failed_login_shows_compact_inline_error(): void
    {
        $this->followingRedirects()
            ->from(route('admin.login'))
            ->post(route('admin.login.submit'), [
                'email' => 'nobody@example.com',
                'password' => 'wrong-password',
            ])
            ->assertSee('cf-flash--danger', false)
            ->assertSee(__('iam::auth.failed'), false);
    }

    public function test_forgot_password_page_uses_the_same_auth_layout(): void
    {
        $this->get(route('admin.password.request'))
            ->assertOk()
            ->assertSee('data-admin-auth', false)
            ->assertSee(__('iam::auth.forgot_password_title'), false)
            ->assertSee(__('iam::auth.forgot_password_submit'), false);
    }

    public function test_successful_login_still_reaches_admin(): void
    {
        $user = User::query()->first();

        $this->post(route('admin.login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }
}
