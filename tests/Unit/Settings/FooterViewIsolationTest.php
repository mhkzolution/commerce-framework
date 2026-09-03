<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;

final class FooterViewIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN = [
        'SettingQueryServiceInterface',
        'Commerce\\Cms\\Models\\Page',
        'FooterSectionConfig',
        'FooterConfigService',
        'FooterConfig',
        'use Commerce\\Settings\\Footer\\DTO\\FooterSection;',
        'title_key',
        "['meta']",
        '$section[',
        '$item[',
        '$viewModel',
        'defaultVariant',
        '::query(',
        'Illuminate\\Database\\Eloquent',
        'SiteIdentity',
        'StorefrontNavigationConfig',
        'WebsiteSettingsService',
        'FooterBrandingQuery',
        'FooterNavigationQuery',
        'FooterSocialQuery',
    ];

    /**
     * @var list<string>
     */
    private const ALLOWED = [
        'FooterPageData',
        'FooterSectionData',
        'FooterBrandData',
        'FooterLinkData',
        '$footer->sections',
        '$section->brand',
        '$section->links',
        '$section->text',
        '$link->url',
        '$link->label',
    ];

    public function test_storefront_footer_view_exists(): void
    {
        $this->assertFileExists($this->bladePath());
    }

    public function test_storefront_footer_view_does_not_bind_models_or_driver_dtos(): void
    {
        $path = $this->bladePath();
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $hits = [];
        foreach (self::FORBIDDEN as $token) {
            if (str_contains($contents, $token)) {
                $hits[] = $path.' contains '.$token;
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_storefront_footer_view_consumes_page_section_brand_and_link_dtos(): void
    {
        $path = $this->bladePath();
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $missing = [];
        foreach (self::ALLOWED as $token) {
            if (! str_contains($contents, $token)) {
                $missing[] = $path.' missing '.$token;
            }
        }

        $this->assertSame([], $missing, implode("\n", $missing));
    }

    private function bladePath(): string
    {
        return dirname(__DIR__, 3).'/resources/views/components/storefront/layout/partials/site-footer.blade.php';
    }
}
