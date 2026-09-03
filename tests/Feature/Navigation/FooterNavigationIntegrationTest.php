<?php

declare(strict_types=1);

namespace Tests\Feature\Navigation;

use Commerce\Navigation\Models\NavigationMenu;
use Commerce\Navigation\Models\NavigationMenuItem;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Services\FooterConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FooterNavigationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(SettingsSeeder::class);
    }

    public function test_storefront_footer_renders_named_menu_links(): void
    {
        $menu = NavigationMenu::query()->where('handle', 'main')->firstOrFail();
        NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Visit Shop',
            'url' => '/shop',
            'position' => 0,
            'is_visible' => true,
            'footer_enabled' => true,
        ]);

        $this->saveFooterConfig([
            'sections' => [
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
            ],
        ]);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('storefront-site-footer__section--links', false)
            ->assertSee('Visit Shop', false)
            ->assertSee('/shop', false);
    }

    public function test_footer_source_footer_reads_footer_menu_not_main(): void
    {
        $main = NavigationMenu::query()->where('handle', 'main')->firstOrFail();
        $footer = NavigationMenu::query()->where('handle', 'footer')->firstOrFail();

        NavigationMenuItem::query()->create([
            'menu_id' => $main->id,
            'label' => 'Main Only',
            'url' => '/main-only',
            'position' => 0,
            'is_visible' => true,
            'footer_enabled' => true,
        ]);
        NavigationMenuItem::query()->create([
            'menu_id' => $footer->id,
            'label' => 'Footer Only',
            'url' => '/footer-only',
            'position' => 0,
            'is_visible' => true,
            'footer_enabled' => true,
        ]);

        $this->saveFooterConfig([
            'sections' => [
                [
                    'id' => 'quick-links',
                    'type' => 'navigation',
                    'enabled' => true,
                    'settings' => [
                        'source' => 'footer',
                        'max_links' => 6,
                        'visibility_mode' => 'footer_enabled_only',
                    ],
                ],
            ],
        ]);

        $html = $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->getContent();

        $footerAt = strpos($html, 'storefront-site-footer');
        $this->assertNotFalse($footerAt);
        $footer = substr($html, $footerAt);

        $this->assertStringContainsString('Footer Only', $footer);
        $this->assertStringNotContainsString('Main Only', $footer);
        $this->assertStringContainsString('Main Only', substr($html, 0, $footerAt));
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
