<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Services\FooterConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FooterSocialIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(SettingsSeeder::class);
    }

    public function test_storefront_footer_renders_social_links_from_website_settings(): void
    {
        app(SettingServiceInterface::class)->updateGroup(new UpdateSettingsGroupData(
            group: 'social',
            values: [
                'facebook' => 'https://facebook.com/harbor',
                'line' => 'https://line.me/harbor',
            ],
        ));

        $this->saveFooterConfig([
            'sections' => [
                [
                    'id' => 'social-links',
                    'type' => 'social',
                    'enabled' => true,
                    'settings' => [],
                ],
            ],
        ]);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('storefront-site-footer__section--social', false)
            ->assertSee('https://facebook.com/harbor', false)
            ->assertSee('https://line.me/harbor', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function saveFooterConfig(array $overrides): void
    {
        $config = app(FooterConfigService::class);
        $config->ensureRegistered();

        app(SettingServiceInterface::class)->updateGroup(new UpdateSettingsGroupData(
            group: 'footer',
            values: [
                'config' => $config->merge($overrides),
            ],
        ));

        $config->forgetResolved();
    }
}
