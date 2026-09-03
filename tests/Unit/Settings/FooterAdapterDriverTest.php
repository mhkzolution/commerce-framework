<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Settings\Footer\Drivers\BrandSectionDriver;
use Commerce\Settings\Footer\Drivers\NavigationSectionDriver;
use Commerce\Settings\Footer\Drivers\SocialSectionDriver;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use Commerce\Settings\Services\FooterBrandingQuery;
use Commerce\Settings\Services\FooterNavigationQuery;
use Commerce\Settings\Services\FooterSocialQuery;
use Tests\TestCase;

final class FooterAdapterDriverTest extends TestCase
{
    public function test_brand_driver_uses_branding_query_not_site_identity(): void
    {
        $driver = new BrandSectionDriver(new FooterBrandingQuery($this->settings([
            'store.name' => 'Acme Store',
        ])));

        $section = $driver->build(new FooterSectionConfig(
            id: 'brand-primary',
            type: 'brand',
            enabled: true,
            visibility: [],
            settings: [
                'show_logo' => false,
                'show_store_name' => true,
                'show_description' => false,
            ],
        ));

        $this->assertNotNull($section);
        $this->assertSame('Acme Store', $section->meta['display_name'] ?? null);
        $this->assertArrayNotHasKey('logo_url', $section->meta);
    }

    public function test_brand_driver_returns_null_when_all_display_toggles_are_false(): void
    {
        $driver = new BrandSectionDriver(new FooterBrandingQuery($this->settings([
            'store.name' => 'Acme Store',
        ])));

        $section = $driver->build(new FooterSectionConfig(
            id: 'brand-primary',
            type: 'brand',
            enabled: true,
            visibility: [],
            settings: [
                'show_logo' => false,
                'show_store_name' => false,
                'show_description' => false,
            ],
        ));

        $this->assertNull($section);
    }

    public function test_navigation_driver_is_empty_without_context_or_recovery(): void
    {
        $driver = new NavigationSectionDriver(new FooterNavigationQuery);

        $section = $driver->build(new FooterSectionConfig(
            id: 'quick-links',
            type: 'navigation',
            enabled: true,
            visibility: [],
            settings: [
                'source' => 'main',
                'max_links' => 6,
                'visibility_mode' => 'footer_enabled_only',
            ],
        ));

        $this->assertNull($section);
    }

    public function test_social_driver_is_empty_without_website_settings(): void
    {
        $driver = new SocialSectionDriver(new FooterSocialQuery);

        $section = $driver->build(new FooterSectionConfig(
            id: 'social-links',
            type: 'social',
            enabled: true,
            visibility: [],
            settings: [],
        ));

        $this->assertNull($section);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function settings(array $values): SettingQueryServiceInterface
    {
        $settings = $this->createMock(SettingQueryServiceInterface::class);
        $settings->method('get')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => $values[$key] ?? $default,
        );
        $settings->method('has')->willReturnCallback(
            static fn (string $key): bool => array_key_exists($key, $values),
        );
        $settings->method('getGroup')->willReturn([]);

        return $settings;
    }
}
