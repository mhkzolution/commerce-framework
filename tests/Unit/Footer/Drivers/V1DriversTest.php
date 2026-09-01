<?php

declare(strict_types=1);

namespace Tests\Unit\Footer\Drivers;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SiteIdentityServiceInterface;
use Commerce\Settings\Footer\Drivers\BrandSectionDriver;
use Commerce\Settings\Footer\Drivers\CopyrightSectionDriver;
use Commerce\Settings\Footer\Drivers\MarketplaceSectionDriver;
use Commerce\Settings\Footer\Drivers\NavigationSectionDriver;
use Commerce\Settings\Footer\Drivers\PoweredBySectionDriver;
use Commerce\Settings\Footer\Drivers\SocialSectionDriver;
use Commerce\Settings\Footer\DTO\FooterBuildContext;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class V1DriversTest extends TestCase
{
    #[Test]
    public function brand_driver_returns_null_when_site_identity_has_no_renderable_data(): void
    {
        $siteIdentity = $this->createMock(SiteIdentityServiceInterface::class);
        $siteIdentity->method('name')->willReturn('');
        $siteIdentity->method('logoUrl')->willReturn(null);

        $settings = $this->createMock(SettingQueryServiceInterface::class);
        $settings->method('get')->willReturn(null);

        $driver = new BrandSectionDriver($siteIdentity, $settings);

        $section = $driver->build($this->config('brand', [
            'show_logo' => true,
            'show_store_name' => true,
            'show_description' => true,
        ]));

        $this->assertNull($section);
    }

    #[Test]
    public function brand_driver_returns_null_when_all_display_toggles_are_false(): void
    {
        $siteIdentity = $this->createMock(SiteIdentityServiceInterface::class);
        $siteIdentity->method('name')->willReturn('Acme Store');
        $siteIdentity->method('logoUrl')->willReturn('https://cdn.test/logo.png');

        $driver = new BrandSectionDriver($siteIdentity);

        $section = $driver->build($this->config('brand', [
            'show_logo' => false,
            'show_store_name' => false,
            'show_description' => false,
        ]));

        $this->assertNull($section);
    }

    #[Test]
    public function navigation_driver_returns_structured_links_from_context_navigation_source(): void
    {
        $driver = new NavigationSectionDriver;

        $section = $driver->build($this->config('navigation', [
            'source' => 'main',
            'max_links' => 2,
            'visibility_mode' => 'footer_enabled_only',
        ], meta: [
            'footer_navigation' => [
                'main' => [
                    ['id' => 'shop', 'label' => 'Shop', 'url' => '/shop', 'footer_enabled' => true],
                    ['id' => 'sale', 'label' => 'Sale', 'url' => '/sale', 'footer_enabled' => false],
                    ['id' => 'about', 'label' => 'About', 'url' => '/about', 'footer_enabled' => true],
                ],
            ],
        ]));

        $this->assertNotNull($section);
        $this->assertSame('footer.section.navigation', $section->titleKey);
        $this->assertSame(['/shop', '/about'], array_column($section->items, 'url'));
        $this->assertSame(2, $section->meta['count']);
    }

    #[Test]
    public function social_driver_returns_null_when_social_links_are_empty(): void
    {
        $siteIdentity = $this->createMock(SiteIdentityServiceInterface::class);
        $siteIdentity->method('socialLinks')->willReturn([]);

        $driver = new SocialSectionDriver($siteIdentity);

        $section = $driver->build($this->config('social'));

        $this->assertNull($section);
    }

    #[Test]
    public function copyright_driver_returns_section_with_template_text(): void
    {
        $driver = new CopyrightSectionDriver;

        $section = $driver->build($this->config('copyright', [
            'template' => 'Copyright {year} {store_name}',
        ]));

        $this->assertNotNull($section);
        $this->assertSame('Copyright {year} {store_name}', $section->meta['text']);
    }

    #[Test]
    public function copyright_driver_returns_null_when_template_is_empty(): void
    {
        $driver = new CopyrightSectionDriver;

        $section = $driver->build($this->config('copyright', [
            'template' => '   ',
        ]));

        $this->assertNull($section);
    }

    #[Test]
    public function powered_by_driver_returns_null_for_enterprise_plans(): void
    {
        $driver = new PoweredBySectionDriver;

        $section = $driver->build($this->config('powered_by', [], planTier: 'enterprise'));

        $this->assertNull($section);
    }

    #[Test]
    public function powered_by_driver_returns_section_for_free_plans(): void
    {
        $driver = new PoweredBySectionDriver;

        $section = $driver->build($this->config('powered_by', [], enabled: false, planTier: 'free'));

        $this->assertNotNull($section);
        $this->assertTrue($section->meta['required']);
        $this->assertSame('free', $section->meta['plan_tier']);
    }

    #[Test]
    public function marketplace_driver_respects_marketplace_feature_availability(): void
    {
        $driver = new MarketplaceSectionDriver;
        $links = [
            ['label' => 'Seller Center', 'url' => '/seller'],
        ];

        $hidden = $driver->build($this->config('marketplace', [], meta: [], enabled: true, planTier: 'pro', featureFlags: [
            'marketplace' => false,
        ]));
        $visible = $driver->build($this->config('marketplace', [], meta: [
            'marketplace_links' => $links,
        ], enabled: true, planTier: 'pro', featureFlags: [
            'marketplace' => true,
        ]));

        $this->assertNull($hidden);
        $this->assertNotNull($visible);
        $this->assertSame($links, $visible->items);
    }

    private function config(
        string $type,
        array $settings = [],
        array $meta = [],
        bool $enabled = true,
        string $planTier = 'pro',
        array $featureFlags = ['marketplace' => true],
    ): FooterSectionConfig {
        return new FooterSectionConfig(
            id: $type.'-section',
            type: $type,
            enabled: $enabled,
            settings: $settings,
            context: new FooterBuildContext(
                device: 'desktop',
                planTier: $planTier,
                featureFlags: $featureFlags,
                serviceAvailability: ['cms' => true],
                meta: $meta,
            ),
        );
    }
}
