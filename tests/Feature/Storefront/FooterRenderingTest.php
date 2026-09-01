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

        $this->seed(SettingsSeeder::class);
        Carbon::setTestNow('2026-08-18 15:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_storefront_footer_renders_only_non_empty_enabled_sections(): void
    {
        $this->saveSiteIdentity([
            'name' => 'Footer Test Shop',
        ]);

        $this->saveFooterConfig([
            'layout' => [
                'columns' => 3,
                'padding' => 'md',
                'spacing' => 'sm',
            ],
            'sections' => [
                [
                    'id' => 'copyright',
                    'type' => 'copyright',
                    'enabled' => true,
                    'settings' => [
                        'template' => 'Copyright {year} {store_name}',
                    ],
                ],
                [
                    'id' => 'empty-cms',
                    'type' => 'cms',
                    'enabled' => true,
                    'settings' => [
                        'page_ids' => [],
                    ],
                ],
                [
                    'id' => 'social-disabled',
                    'type' => 'social',
                    'enabled' => false,
                    'settings' => [],
                ],
            ],
        ]);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('Copyright 2026 Footer Test Shop', false)
            ->assertSee('cf-footer-cols-3', false)
            ->assertSee('cf-footer-padding-md', false)
            ->assertSee('cf-footer-spacing-sm', false)
            ->assertDontSee('storefront-site-footer__section--social', false)
            ->assertDontSee('footer.section.marketplace', false);
    }

    public function test_storefront_footer_gracefully_skips_malformed_and_empty_marketplace_sections(): void
    {
        $this->saveSiteIdentity([
            'name' => 'Acme Store',
        ]);

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
            ->assertDontSee('footer.section.marketplace', false);
    }

    public function test_footer_component_hides_heading_when_translation_key_is_missing_and_uses_safe_aria_fallbacks(): void
    {
        $html = view('components.storefront.layout.partials.site-footer', [
            'viewModel' => [
                'layout' => [
                    'columns' => ['grid_class' => 'cf-footer-cols-2'],
                    'divider' => ['class' => 'cf-footer-divider-solid'],
                    'padding' => ['class' => 'cf-footer-padding-md'],
                    'spacing' => ['class' => 'cf-footer-spacing-sm'],
                    'theme' => ['classes' => ['cf-footer-scheme-default']],
                ],
                'sections' => [
                    [
                        'id' => 'social-primary',
                        'type' => 'social',
                        'title_key' => 'footer.section.missing_label',
                        'items' => [
                            [
                                'key' => 'instagram',
                                'label' => 'Instagram',
                                'url' => 'https://example.com/instagram',
                            ],
                        ],
                        'meta' => [],
                    ],
                ],
            ],
        ])->render();

        $this->assertStringNotContainsString('footer.section.missing_label', $html);
        $this->assertStringNotContainsString('<h2 class="storefront-site-footer__heading">', $html);
        $this->assertStringContainsString('aria-label="Social links"', $html);
        $this->assertStringContainsString('aria-label="Instagram"', $html);
    }

    public function test_footer_component_renders_navigation_sections_with_semantic_navigation_lists(): void
    {
        $html = view('components.storefront.layout.partials.site-footer', [
            'viewModel' => [
                'layout' => [
                    'columns' => ['grid_class' => 'cf-footer-cols-2'],
                    'divider' => ['class' => 'cf-footer-divider-solid'],
                    'padding' => ['class' => 'cf-footer-padding-md'],
                    'spacing' => ['class' => 'cf-footer-spacing-sm'],
                    'theme' => ['classes' => ['cf-footer-scheme-default']],
                ],
                'sections' => [
                    [
                        'id' => 'main-nav',
                        'type' => 'navigation',
                        'title_key' => 'footer.section.navigation',
                        'items' => [
                            ['label' => 'Shop', 'url' => '/shop'],
                            ['label' => 'Brands', 'url' => '/brands'],
                        ],
                        'meta' => [],
                    ],
                    [
                        'id' => 'help-pages',
                        'type' => 'cms',
                        'title_key' => 'footer.section.cms',
                        'items' => [
                            ['label' => 'FAQ', 'url' => '/faq'],
                        ],
                        'meta' => [],
                    ],
                ],
            ],
        ])->render();

        $this->assertSame(2, substr_count($html, '<nav class="storefront-site-footer__section storefront-site-footer__section--links"'));
        $this->assertSame(2, substr_count($html, '<ul class="storefront-site-footer__list" role="list">'));
        $this->assertGreaterThanOrEqual(3, substr_count($html, '<li>'));
        $this->assertStringContainsString('>Navigation<', $html);
        $this->assertStringContainsString('>Information<', $html);
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
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function saveSiteIdentity(array $values): void
    {
        app(SettingServiceInterface::class)->updateGroup(new UpdateSettingsGroupData(
            group: 'site',
            values: $values,
        ));
    }
}
