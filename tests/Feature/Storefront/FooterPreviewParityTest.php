<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Footer\DTO\FooterBuildContext;
use Commerce\Settings\Services\FooterConfigService;
use Commerce\Settings\Services\FooterViewModelBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class FooterPreviewParityTest extends TestCase
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

    public function test_storefront_footer_matches_shared_component_render_for_same_config(): void
    {
        $this->saveStoreName('Parity Test Shop');

        $overrides = [
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
                        'show_description' => true,
                    ],
                ],
                [
                    'id' => 'help-pages',
                    'type' => 'cms',
                    'enabled' => true,
                    'settings' => [
                        'page_ids' => [],
                    ],
                ],
                [
                    'id' => 'copyright',
                    'type' => 'copyright',
                    'enabled' => true,
                    'settings' => [
                        'template' => 'Copyright {year} {store_name}',
                    ],
                ],
            ],
        ];

        $mergedConfig = app(FooterConfigService::class)->merge($overrides);
        $this->saveFooterConfig($overrides);

        $footer = app(FooterViewModelBuilder::class)->build(
            $mergedConfig,
            new FooterBuildContext(device: null),
        );

        $expectedFooterHtml = $this->normalizeFooterHtml(
            view('components.storefront.layout.partials.site-footer', [
                'footer' => $footer,
            ])->render(),
        );

        $response = $this->get(route('storefront.shop.index'))->assertOk();
        $actualFooterHtml = $this->normalizeFooterHtml(
            $this->extractFooterHtml($response->getContent()),
        );

        $this->assertStringContainsString('Parity Test Shop', $expectedFooterHtml);
        $this->assertStringContainsString('Copyright 2026 Parity Test Shop', $expectedFooterHtml);
        $this->assertStringNotContainsString('storefront-site-footer__section--links', $expectedFooterHtml);
        $this->assertSame($expectedFooterHtml, $actualFooterHtml);
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

    private function extractFooterHtml(string $html): string
    {
        preg_match('/<footer\b[^>]*storefront-site-footer[^>]*>.*?<\/footer>/si', $html, $matches);

        return $matches[0] ?? '';
    }

    private function normalizeFooterHtml(string $html): string
    {
        $normalized = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/>\s+</', '><', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }
}
