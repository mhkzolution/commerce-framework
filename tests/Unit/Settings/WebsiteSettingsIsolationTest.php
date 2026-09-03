<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

final class WebsiteSettingsIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN = [
        'SiteIdentityServiceInterface',
        'SiteIdentityService',
        'WebsiteSettingsService',
        'AppearanceController',
        'CustomerExperienceController',
        'CustomerExperienceConfig',
        'StorefrontNavigationConfig',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_FILES = [
        'src/Http/Controllers/Admin/WebsiteSettingsController.php',
        'src/Http/Requests/UpdateWebsiteSettingsRequest.php',
        'src/Services/WebsiteSettingsQueryService.php',
        'resources/views/admin/website/index.blade.php',
        'resources/lang/en/website.php',
        'resources/lang/th/website.php',
    ];

    public function test_website_settings_surfaces_exist(): void
    {
        foreach (self::REQUIRED_FILES as $relative) {
            $this->assertFileExists($this->settingsRoot().'/'.$relative);
        }
    }

    public function test_website_settings_files_have_no_forbidden_archive_types(): void
    {
        $hits = [];

        foreach ($this->websiteFiles() as $path) {
            $this->assertFileExists($path);
            $contents = file_get_contents($path);
            $this->assertNotFalse($contents, $path);

            foreach (self::FORBIDDEN as $token) {
                if (str_contains($contents, $token)) {
                    $hits[] = $path.' contains '.$token;
                }
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_admin_sidebar_points_at_website_settings_not_site_identity(): void
    {
        $path = $this->repoRoot().'/config/admin.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $this->assertStringContainsString("'route' => 'admin.settings.website.show'", $contents);
        $this->assertStringNotContainsString("'route' => 'admin.settings.site-identity.show'", $contents);
    }

    public function test_footer_social_query_reads_website_settings_contract_without_eloquent(): void
    {
        $path = $this->settingsRoot().'/src/Services/FooterSocialQuery.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        foreach (self::FORBIDDEN as $token) {
            $this->assertStringNotContainsString($token, $contents);
        }

        $this->assertStringContainsString('WebsiteSettingsQueryServiceInterface', $contents);
        $this->assertStringNotContainsString('Commerce\\Settings\\Models\\Setting', $contents);

        $class = 'Commerce\\Settings\\Services\\FooterSocialQuery';
        $this->assertTrue(class_exists($class), $class.' must exist');

        foreach ((new ReflectionClass($class))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $name = $type->getName();
            $this->assertSame('Commerce\\Contracts\\Settings\\WebsiteSettingsQueryServiceInterface', $name);
            $this->assertTrue($type->allowsNull(), 'FooterSocialQuery must fail-soft when Website Settings is unbound');
        }
    }

    public function test_branding_queries_keep_reading_store_keys(): void
    {
        foreach ([
            $this->settingsRoot().'/src/Services/FooterBrandingQuery.php',
            $this->repoRoot().'/modules/Cart/src/Services/HomepageBrandingQuery.php',
        ] as $path) {
            $contents = file_get_contents($path);
            $this->assertNotFalse($contents, $path);
            $this->assertStringContainsString('store.name', $contents);
            $this->assertStringContainsString('store.logo_media_uuid', $contents);
            $this->assertStringContainsString('SettingQueryServiceInterface', $contents);
            $this->assertStringNotContainsString('WebsiteSettingsService', $contents);
            $this->assertStringNotContainsString('SiteIdentityServiceInterface', $contents);
        }
    }

    public function test_footer_renderer_does_not_import_website_settings(): void
    {
        $hits = [];
        $forbidden = [
            'WebsiteSettingsQueryServiceInterface',
            'WebsiteSettingsController',
            'WebsiteSettingsService',
            'SiteIdentityServiceInterface',
        ];

        foreach ([
            $this->repoRoot().'/resources/views/components/storefront/layout/partials/site-footer.blade.php',
            $this->settingsRoot().'/src/Footer/DTO/FooterPageData.php',
            $this->settingsRoot().'/src/Footer/DTO/FooterBrandData.php',
            $this->settingsRoot().'/src/Footer/DTO/FooterLinkData.php',
            $this->settingsRoot().'/src/Footer/Drivers/SocialSectionDriver.php',
        ] as $path) {
            $contents = file_get_contents($path);
            $this->assertNotFalse($contents, $path);

            foreach ($forbidden as $token) {
                if (str_contains($contents, $token)) {
                    $hits[] = $path.' contains '.$token;
                }
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_no_website_module_directory(): void
    {
        $this->assertDirectoryDoesNotExist($this->repoRoot().'/modules/Website');
    }

    /**
     * @return list<string>
     */
    private function websiteFiles(): array
    {
        $root = $this->settingsRoot();

        return [
            $root.'/src/Http/Controllers/Admin/WebsiteSettingsController.php',
            $root.'/src/Http/Requests/UpdateWebsiteSettingsRequest.php',
            $root.'/src/Services/WebsiteSettingsQueryService.php',
            $root.'/src/Services/FooterSocialQuery.php',
            $root.'/resources/views/admin/website/index.blade.php',
            $this->repoRoot().'/packages/commerce/contracts/src/Settings/WebsiteSettingsQueryServiceInterface.php',
            $this->repoRoot().'/packages/commerce/contracts/src/Settings/WebsiteSocialLinkData.php',
        ];
    }

    private function settingsRoot(): string
    {
        return $this->repoRoot().'/modules/Settings';
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
