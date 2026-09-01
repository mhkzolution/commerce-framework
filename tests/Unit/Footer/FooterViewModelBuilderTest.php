<?php

declare(strict_types=1);

namespace Tests\Unit\Footer;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SiteIdentityServiceInterface;
use Commerce\Settings\Footer\DTO\FooterBuildContext;
use Commerce\Settings\Footer\Registry\FooterSectionRegistry;
use Commerce\Settings\Services\FooterSectionManager;
use Commerce\Settings\Services\FooterViewModelBuilder;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FooterViewModelBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function it_resolves_year_and_store_name_placeholders_in_the_view_model(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-18 14:59:00'));

        $siteIdentity = $this->createMock(SiteIdentityServiceInterface::class);
        $siteIdentity->method('name')->willReturn('Acme Store');
        $siteIdentity->method('logoUrl')->willReturn(null);

        $settings = $this->createMock(SettingQueryServiceInterface::class);
        $settings->method('get')->willReturnMap([
            ['site.description', null, 'Trusted everyday essentials'],
            ['store.description', null, null],
        ]);

        $this->app->instance(SiteIdentityServiceInterface::class, $siteIdentity);
        $this->app->instance(SettingQueryServiceInterface::class, $settings);

        $builder = $this->builder($siteIdentity);

        $viewModel = $builder->build([
            'layout' => [
                'columns' => 4,
            ],
            'sections' => [
                [
                    'id' => 'copyright',
                    'type' => 'copyright',
                    'settings' => [
                        'template' => 'Copyright {year} {store_name}',
                    ],
                ],
                [
                    'id' => 'brand-primary',
                    'type' => 'brand',
                    'settings' => [
                        'show_logo' => false,
                        'show_store_name' => true,
                        'show_description' => false,
                    ],
                ],
            ],
        ], $this->context());

        $this->assertSame('Copyright 2026 Acme Store', $viewModel['sections'][0]['meta']['text']);
        $this->assertSame('Acme Store', $viewModel['sections'][1]['meta']['display_name']);
    }

    #[Test]
    public function it_removes_empty_sections_and_preserves_non_empty_ordering(): void
    {
        $builder = $this->builder();

        $viewModel = $builder->build([
            'sections' => [
                [
                    'id' => 'quick-links',
                    'type' => 'navigation',
                    'settings' => [
                        'source' => 'main',
                        'max_links' => 6,
                        'visibility_mode' => 'all',
                    ],
                ],
                [
                    'id' => 'empty-cms',
                    'type' => 'cms',
                    'settings' => [
                        'page_ids' => [],
                    ],
                ],
                [
                    'id' => 'powered-by',
                    'type' => 'powered_by',
                ],
            ],
        ], $this->context());

        $this->assertSame(
            ['quick-links', 'powered-by'],
            array_column($viewModel['sections'], 'id'),
        );
    }

    #[Test]
    public function it_includes_schema_version_and_normalized_layout_tokens(): void
    {
        $builder = $this->builder();

        $viewModel = $builder->build([
            'layout' => [
                'columns' => '5',
                'color_scheme' => 'Brand Dark',
                'surface' => 'Elevated Footer',
                'variant' => 'Muted',
                'divider_style' => 'Dashed Accent',
                'padding' => '2xl cozy',
                'spacing' => 'Roomy',
            ],
            'sections' => [
                [
                    'id' => 'brand-primary',
                    'type' => 'brand',
                ],
            ],
        ], $this->context());

        $this->assertSame(1, $viewModel['schema_version']);
        $this->assertSame(5, $viewModel['layout']['columns']['value']);
        $this->assertSame('cols-5', $viewModel['layout']['columns']['token']);
        $this->assertSame('cf-footer-cols-5', $viewModel['layout']['columns']['grid_class']);
        $this->assertSame('brand-dark', $viewModel['layout']['theme']['color_scheme']);
        $this->assertSame('surface-elevated-footer', $viewModel['layout']['theme']['tokens']['surface']);
        $this->assertSame('divider-dashed-accent', $viewModel['layout']['divider']['token']);
        $this->assertSame('padding-2xl-cozy', $viewModel['layout']['padding']['token']);
        $this->assertSame('spacing-roomy', $viewModel['layout']['spacing']['token']);
    }

    private function builder(?SiteIdentityServiceInterface $siteIdentity = null): FooterViewModelBuilder
    {
        return new FooterViewModelBuilder(
            new FooterSectionManager(
                new FooterSectionRegistry,
                $this->app,
            ),
            $siteIdentity,
        );
    }

    private function context(): FooterBuildContext
    {
        return new FooterBuildContext(
            device: 'desktop',
            planTier: 'pro',
            featureFlags: ['marketplace' => true],
            serviceAvailability: ['cms' => true],
            meta: [
                'footer_navigation' => [
                    'main' => [
                        ['id' => 'shop', 'label' => 'Shop', 'url' => '/shop', 'footer_enabled' => true],
                    ],
                ],
            ],
        );
    }
}
