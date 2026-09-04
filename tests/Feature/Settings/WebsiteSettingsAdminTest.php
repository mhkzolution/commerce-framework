<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\WebsiteSettingsQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Media\Models\Media;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\Services\FooterSocialQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WebsiteSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_and_save_website_settings(): void
    {
        $logo = $this->createMedia('harbor-logo.png');
        $og = $this->createMedia('harbor-og.png');

        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.website.show'))
            ->assertOk()
            ->assertSee(__('settings::website.title'), false)
            ->assertSee(__('settings::website.contact'), false)
            ->assertSee(__('settings::website.seo'), false)
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="social[facebook]"', false)
            ->assertSee('name="seo_title_suffix"', false)
            ->assertSee('name="seo_default_description"', false)
            ->assertSee('name="seo_og_image_media_uuid"', false)
            ->assertSee('data-file-attach', false);

        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.website.update'), [
                'name' => 'Harbor Shop',
                'description' => 'Harbor on the coast',
                'logo_media_uuid' => $logo->uuid,
                'email' => 'hello@harbor.test',
                'phone' => '+66 2 123 4567',
                'social' => [
                    'facebook' => 'https://facebook.com/harbor',
                    'instagram' => '',
                    'tiktok' => 'https://tiktok.com/@harbor',
                    'line' => '',
                ],
                'seo_title_suffix' => 'Harbor Shop',
                'seo_default_description' => 'Coastal goods from Harbor.',
                'seo_og_image_media_uuid' => $og->uuid,
            ])
            ->assertRedirect(route('admin.settings.website.show'));

        $settings = app(SettingQueryServiceInterface::class);

        $this->assertSame('Harbor Shop', $settings->get('store.name'));
        $this->assertSame('Harbor on the coast', $settings->get('store.description'));
        $this->assertSame($logo->uuid, $settings->get('store.logo_media_uuid'));
        $this->assertSame('hello@harbor.test', $settings->get('store.email'));
        $this->assertSame('+66 2 123 4567', $settings->get('store.phone'));
        $this->assertSame('https://facebook.com/harbor', $settings->get('social.facebook'));
        $this->assertSame('https://tiktok.com/@harbor', $settings->get('social.tiktok'));
        $this->assertNull($settings->get('social.instagram'));
        $this->assertNull($settings->get('social.line'));
        $this->assertSame('Harbor Shop', $settings->get('website.seo.title_suffix'));
        $this->assertSame('Coastal goods from Harbor.', $settings->get('website.seo.default_description'));
        $this->assertSame($og->uuid, $settings->get('website.seo.default_og_image_media_uuid'));

        $website = app(WebsiteSettingsQueryServiceInterface::class);

        $this->assertSame('Harbor Shop', $website->brand()->name);
        $this->assertSame('Harbor on the coast', $website->brand()->description);
        $this->assertNotNull($website->brand()->logoUrl);

        $this->assertSame('hello@harbor.test', $website->contact()->email);
        $this->assertSame('+66 2 123 4567', $website->contact()->phone);

        $this->assertSame('Harbor Shop', $website->seoDefaults()->titleSuffix);
        $this->assertSame('Coastal goods from Harbor.', $website->seoDefaults()->defaultDescription);
        $this->assertNotNull($website->seoDefaults()->defaultOgImageUrl);

        $links = $website->socialLinks();
        $this->assertCount(2, $links);
        $this->assertSame('facebook', $links[0]->key);
        $this->assertSame('https://facebook.com/harbor', $links[0]->url);

        $this->assertSame([
            [
                'label' => 'Facebook',
                'url' => 'https://facebook.com/harbor',
                'key' => 'facebook',
            ],
            [
                'label' => 'TikTok',
                'url' => 'https://tiktok.com/@harbor',
                'key' => 'tiktok',
            ],
        ], app(FooterSocialQuery::class)->links());
    }

    public function test_legacy_site_identity_route_shows_site_identity_page(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.site-identity.show'))
            ->assertOk()
            ->assertSee(__('settings::admin.site_identity_title'), false)
            ->assertSee(__('settings::admin.favicon'), false);
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
