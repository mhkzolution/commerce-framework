<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SiteIdentityServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Media\Models\Media;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SiteIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_admin_can_view_site_identity_page(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.site-identity.show'))
            ->assertOk()
            ->assertSee(__('settings::admin.site_identity_title'), false)
            ->assertSee(__('settings::admin.contact_social'), false)
            ->assertSee(__('settings::admin.site_logo'), false)
            ->assertSee(__('settings::admin.favicon'), false);
    }

    public function test_admin_can_save_site_identity_settings(): void
    {
        $logo = $this->createMedia('logo.png');
        $favicon = $this->createMedia('favicon.png');

        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.site-identity.update'), [
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
            ->assertRedirect(route('admin.settings.site-identity.show'));

        $settings = app(SettingQueryServiceInterface::class);
        $site = app(SiteIdentityServiceInterface::class);

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

        $this->assertSame('My Shop', $site->name());
        $this->assertStringContainsString($logo->path, (string) $site->logoUrl());
        $this->assertStringContainsString($favicon->path, (string) $site->faviconUrl());
        $this->assertCount(4, $site->socialLinks());
    }

    public function test_storefront_reflects_site_identity_settings(): void
    {
        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.site-identity.update'), [
                'name' => 'Aura Commerce',
                'contact_email' => 'support@aura.test',
                'social_facebook' => 'https://facebook.com/aura',
            ]);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('Aura Commerce', false)
            ->assertSee('support@aura.test', false)
            ->assertSee('https://facebook.com/aura', false);
    }

    public function test_public_settings_api_includes_site_identity(): void
    {
        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.site-identity.update'), [
                'name' => 'API Shop',
                'contact_phone' => '+66123456789',
            ]);

        $this->getJson(route('api.v1.settings.public'))
            ->assertOk()
            ->assertJsonPath('data.site.name', 'API Shop')
            ->assertJsonPath('data.site.contact_phone', '+66123456789');
    }

    private function createMedia(string $filename): Media
    {
        $file = UploadedFile::fake()->image($filename, 64, 64);
        $path = $file->store('media', 'public');

        return Media::query()->create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'path' => $path,
            'filename' => basename($path),
            'original_filename' => $filename,
            'mime_type' => 'image/png',
            'size' => $file->getSize(),
            'media_type' => 'image',
            'alt_text' => $filename,
        ]);
    }
}
