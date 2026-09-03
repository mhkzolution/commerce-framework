<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Services\FooterConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class FooterRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(SettingsSeeder::class);
        Carbon::setTestNow('2026-08-18 15:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_storefront_footer_renders_brand_copyright_and_powered_by_and_fail_softs_empty_nav_social(): void
    {
        $this->saveStoreName('Footer Test Shop');

        $this->saveFooterConfig([
            'layout' => [
                'columns' => 3,
                'padding' => 'md',
                'spacing' => 'sm',
            ],
            'sections' => [
                [
                    'id' => 'brand-primary',
                    'type' => 'brand',
                    'enabled' => true,
                    'settings' => [
                        'show_logo' => false,
                        'show_store_name' => true,
                        'show_description' => false,
                    ],
                ],
                [
                    'id' => 'quick-links',
                    'type' => 'navigation',
                    'enabled' => true,
                    'settings' => [
                        'source' => 'main',
                        'max_links' => 6,
                        'visibility_mode' => 'footer_enabled_only',
                    ],
                ],
                [
                    'id' => 'social-links',
                    'type' => 'social',
                    'enabled' => true,
                    'settings' => [],
                ],
                [
                    'id' => 'copyright',
                    'type' => 'copyright',
                    'enabled' => true,
                    'settings' => [
                        'template' => 'Copyright {year} {store_name}',
                    ],
                ],
                [
                    'id' => 'powered-by',
                    'type' => 'powered_by',
                    'enabled' => true,
                    'settings' => [],
                ],
            ],
        ]);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('storefront-site-footer', false)
            ->assertSee('Footer Test Shop', false)
            ->assertSee('Copyright 2026 Footer Test Shop', false)
            ->assertSee('Powered by Commerce Framework', false)
            ->assertSee('cf-footer-cols-3', false)
            ->assertSee('cf-footer-padding-md', false)
            ->assertSee('cf-footer-spacing-sm', false)
            ->assertDontSee('storefront-site-footer__section--social', false)
            ->assertDontSee('storefront-site-footer__section--links', false);

        $this->get('/')
            ->assertOk()
            ->assertSee('storefront-site-footer', false)
            ->assertSee('Copyright 2026 Footer Test Shop', false);
    }

    public function test_storefront_footer_gracefully_skips_malformed_and_empty_marketplace_sections(): void
    {
        $this->saveStoreName('Acme Store');

        $this->saveFooterConfig([
            'sections' => [
                'broken-section',
                [
                    'id' => 'marketplace',
                    'type' => 'marketplace',
                    'enabled' => true,
                    'settings' => [],
                ],
                [
                    'id' => 'copyright',
                    'type' => 'copyright',
                    'enabled' => true,
                    'settings' => [
                        'template' => 'Footer stable {year}',
                    ],
                ],
            ],
        ]);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('Footer stable 2026', false)
            ->assertDontSee('footer.section.marketplace', false)
            ->assertDontSee('storefront-site-footer__section--links', false);
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

    private function saveStoreName(string $name): void
    {
        app(SettingServiceInterface::class)->updateGroup(new UpdateSettingsGroupData(
            group: 'store',
            values: [
                'name' => $name,
            ],
        ));
    }
}
