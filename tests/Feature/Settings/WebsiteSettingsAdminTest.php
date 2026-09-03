<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\WebsiteSettingsQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
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
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.website.show'))
            ->assertOk()
            ->assertSee(__('settings::website.title'), false)
            ->assertSee('name="name"', false)
            ->assertSee('name="social[facebook]"', false);

        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.website.update'), [
                'name' => 'Harbor Shop',
                'description' => 'Harbor on the coast',
                'social' => [
                    'facebook' => 'https://facebook.com/harbor',
                    'instagram' => '',
                    'tiktok' => 'https://tiktok.com/@harbor',
                    'line' => '',
                ],
            ])
            ->assertRedirect(route('admin.settings.website.show'));

        $settings = app(SettingQueryServiceInterface::class);

        $this->assertSame('Harbor Shop', $settings->get('store.name'));
        $this->assertSame('Harbor on the coast', $settings->get('store.description'));
        $this->assertSame('https://facebook.com/harbor', $settings->get('social.facebook'));
        $this->assertSame('https://tiktok.com/@harbor', $settings->get('social.tiktok'));
        $this->assertNull($settings->get('social.instagram'));
        $this->assertNull($settings->get('social.line'));

        $links = app(WebsiteSettingsQueryServiceInterface::class)->socialLinks();
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

    public function test_legacy_site_identity_route_aliases_website_settings(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.site-identity.show'))
            ->assertOk()
            ->assertSee(__('settings::website.title'), false);
    }
}
