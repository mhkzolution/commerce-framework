<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Media\Models\Media;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SiteIdentityAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_site_identity_page(): void
    {
        $this->actingAs(User::query()->first())
            ->get('/admin/settings/site-identity')
            ->assertOk()
            ->assertSee(__('settings::admin.site_identity_title'))
            ->assertSee(__('settings::admin.contact_social'))
            ->assertSee(__('settings::admin.site_logo'))
            ->assertSee(__('settings::admin.favicon'), false)
            ->assertSee('data-file-attach', false);
    }

    public function test_admin_can_save_site_identity_settings(): void
    {
        $logo = $this->createMedia('logo.png');
        $favicon = $this->createMedia('favicon.png');

        $this->actingAs(User::query()->first())
            ->put('/admin/settings/site-identity', [
                'name' => 'My Shop',
                'logo_media_uuid' => $logo->uuid,
                'favicon_media_uuid' => $favicon->uuid,
                'contact_address' => '123 Sukhumvit Rd',
                'contact_email' => 'hello@example.com',
                'contact_phone' => '+66 2 123 4567',
                'social_facebook' => 'https://facebook.com/myshop',
                'social_instagram' => 'https://instagram.com/myshop',
                'social_tiktok' => 'https://tiktok.com/@myshop',
                'social_line' => 'https://line.me/R/ti/p/@myshop',
            ])
            ->assertRedirect('/admin/settings/site-identity');

        $settings = app(SettingQueryServiceInterface::class);

        $this->assertSame('My Shop', $settings->get('site.name'));
        $this->assertSame($logo->uuid, $settings->get('site.logo_media_uuid'));
        $this->assertSame($favicon->uuid, $settings->get('site.favicon_media_uuid'));
        $this->assertSame('123 Sukhumvit Rd', $settings->get('site.contact_address'));
        $this->assertSame('hello@example.com', $settings->get('site.contact_email'));
        $this->assertSame('+66 2 123 4567', $settings->get('site.contact_phone'));
        $this->assertSame('https://facebook.com/myshop', $settings->get('site.social_facebook'));
        $this->assertSame('https://instagram.com/myshop', $settings->get('site.social_instagram'));
        $this->assertSame('https://tiktok.com/@myshop', $settings->get('site.social_tiktok'));
        $this->assertSame('https://line.me/R/ti/p/@myshop', $settings->get('site.social_line'));

        $this->assertSame('My Shop', $settings->get('store.name'));
        $this->assertSame($logo->uuid, $settings->get('store.logo_media_uuid'));
        $this->assertSame('https://facebook.com/myshop', $settings->get('social.facebook'));
        $this->assertSame('https://line.me/R/ti/p/@myshop', $settings->get('social.line'));
    }

    private function createMedia(string $filename): Media
    {
        return Media::query()->create([
            'filename' => $filename,
            'original_filename' => $filename,
            'mime_type' => 'image/png',
            'media_type' => 'image',
            'size' => 1024,
            'disk' => 'public',
            'path' => 'media/'.$filename,
        ]);
    }
}
