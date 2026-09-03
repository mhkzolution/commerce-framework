<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Settings\Footer\Drivers\BrandSectionDriver;
use Commerce\Settings\Footer\Drivers\CmsSectionDriver;
use Commerce\Settings\Footer\Drivers\MarketplaceSectionDriver;
use Commerce\Settings\Footer\Drivers\NavigationSectionDriver;
use Commerce\Settings\Footer\Drivers\SocialSectionDriver;
use Commerce\Settings\Http\Controllers\Admin\FooterController;
use Commerce\Settings\Services\FooterConfigService;
use Commerce\Settings\Services\FooterViewModelBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

final class FooterIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN = [
        'SiteIdentityServiceInterface',
        'SiteIdentityService',
        'StorefrontNavigationConfig',
        'WebsiteSettingsService',
        'AppearanceController',
        'CustomerExperienceController',
        'CustomerExperienceConfig',
        'FooterBrandingQuery',
        'FooterNavigationQuery',
        'FooterSocialQuery',
    ];

    /**
     * @var list<class-string>
     */
    private const CONSTRUCTOR_GRAPH = [
        FooterController::class,
        FooterConfigService::class,
        FooterViewModelBuilder::class,
        BrandSectionDriver::class,
        SocialSectionDriver::class,
        NavigationSectionDriver::class,
        CmsSectionDriver::class,
        MarketplaceSectionDriver::class,
    ];

    public function test_m1_source_files_exist(): void
    {
        foreach ($this->m1PhpAndBladeFiles() as $path) {
            $this->assertFileExists($path);
        }
    }

    public function test_m1_files_do_not_contain_forbidden_imports(): void
    {
        $hits = [];

        foreach ($this->m1PhpAndBladeFiles() as $path) {
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

    public function test_settings_provider_and_routes_do_not_recover_website_settings(): void
    {
        $hits = [];

        foreach ([
            $this->settingsRoot().'/src/SettingsServiceProvider.php',
            $this->settingsRoot().'/routes/web.php',
        ] as $path) {
            $contents = file_get_contents($path);
            $this->assertNotFalse($contents, $path);

            foreach (self::FORBIDDEN as $token) {
                if (str_contains($contents, $token)) {
                    $hits[] = $path.' contains '.$token;
                }
            }
        }

        $provider = file_get_contents($this->settingsRoot().'/src/SettingsServiceProvider.php');
        $this->assertNotFalse($provider);
        $this->assertStringContainsString('FooterConfigService', $provider);
        $this->assertStringContainsString('FooterSectionRegistry', $provider);
        $this->assertStringContainsString('FooterViewModelBuilder', $provider);
        $this->assertStringContainsString('site-footer', $provider);
        $this->assertStringContainsString('FooterPageData', $provider);
        $this->assertStringNotContainsString('FooterBrandingQuery', $provider);
        $this->assertStringNotContainsString('FooterNavigationQuery', $provider);
        $this->assertStringNotContainsString('FooterSocialQuery', $provider);

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_constructor_graph_excludes_forbidden_types_and_keeps_allowed_owners(): void
    {
        $forbiddenHits = [];
        $sawFooterConfig = false;
        $sawSettingsQuery = false;

        foreach (self::CONSTRUCTOR_GRAPH as $class) {
            $this->assertTrue(class_exists($class), $class.' must exist');

            foreach ((new ReflectionClass($class))->getConstructor()?->getParameters() ?? [] as $parameter) {
                $type = $parameter->getType();
                if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    continue;
                }

                $name = $type->getName();

                foreach (self::FORBIDDEN as $token) {
                    if (str_contains($name, $token)) {
                        $forbiddenHits[] = $class.' injects '.$name;
                    }
                }

                if ($name === FooterConfigService::class) {
                    $sawFooterConfig = true;
                }

                if ($name === SettingQueryServiceInterface::class) {
                    $sawSettingsQuery = true;
                }
            }
        }

        $this->assertTrue($sawFooterConfig, 'FooterController must inject FooterConfigService');
        $this->assertTrue($sawSettingsQuery, 'M1 graph must inject SettingQueryServiceInterface');
        $this->assertSame([], $forbiddenHits, implode("\n", $forbiddenHits));
    }

    public function test_m2_ships_storefront_footer_without_false_friends(): void
    {
        $repo = dirname(__DIR__, 3);

        $this->assertFileExists($repo.'/resources/views/components/storefront/layout/partials/site-footer.blade.php');
        $this->assertFileExists($repo.'/resources/css/storefront/footer.css');
        $this->assertFileDoesNotExist($repo.'/resources/views/components/storefront/auth/auth-footer.blade.php');
        $this->assertFileDoesNotExist($repo.'/resources/views/components/storefront/checkout/footer.blade.php');
        $this->assertDirectoryDoesNotExist($repo.'/modules/Footer');
    }

    public function test_presenter_builds_page_data_without_page_model_or_m3_queries(): void
    {
        $path = $this->settingsRoot().'/src/Services/FooterViewModelBuilder.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $this->assertStringContainsString('FooterPageData', $contents);
        $this->assertStringContainsString('FooterSectionData', $contents);
        $this->assertStringContainsString('FooterBrandData', $contents);
        $this->assertStringContainsString('FooterLinkData', $contents);
        $this->assertStringNotContainsString('Commerce\\Cms\\Models\\Page', $contents);
        $this->assertStringNotContainsString('FooterBrandingQuery', $contents);
        $this->assertStringNotContainsString('FooterNavigationQuery', $contents);
        $this->assertStringNotContainsString('FooterSocialQuery', $contents);
    }

    public function test_cart_layout_mounts_shared_footer_and_css(): void
    {
        $path = dirname(__DIR__, 3).'/modules/Cart/resources/views/layouts/storefront.blade.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $this->assertStringContainsString('x-storefront.layout.partials.site-footer', $contents);
        $this->assertStringContainsString('resources/css/storefront/footer.css', $contents);
    }

    /**
     * @return list<string>
     */
    private function m1PhpAndBladeFiles(): array
    {
        $root = $this->settingsRoot();

        return [
            $root.'/src/Footer/Contracts/FooterSectionDriver.php',
            $root.'/src/Footer/DTO/FooterBuildContext.php',
            $root.'/src/Footer/DTO/FooterSection.php',
            $root.'/src/Footer/DTO/FooterSectionConfig.php',
            $root.'/src/Footer/DTO/FooterPageData.php',
            $root.'/src/Footer/DTO/FooterSectionData.php',
            $root.'/src/Footer/DTO/FooterBrandData.php',
            $root.'/src/Footer/DTO/FooterLinkData.php',
            $root.'/src/Footer/Registry/FooterSectionRegistry.php',
            $root.'/src/Footer/Drivers/BrandSectionDriver.php',
            $root.'/src/Footer/Drivers/CmsSectionDriver.php',
            $root.'/src/Footer/Drivers/CopyrightSectionDriver.php',
            $root.'/src/Footer/Drivers/MarketplaceSectionDriver.php',
            $root.'/src/Footer/Drivers/NavigationSectionDriver.php',
            $root.'/src/Footer/Drivers/PoweredBySectionDriver.php',
            $root.'/src/Footer/Drivers/SocialSectionDriver.php',
            $root.'/src/Services/FooterConfigService.php',
            $root.'/src/Services/FooterSectionManager.php',
            $root.'/src/Services/FooterViewModelBuilder.php',
            $root.'/src/Http/Controllers/Admin/FooterController.php',
            $root.'/src/Http/Requests/Concerns/NormalizesFooterConfig.php',
            $root.'/src/Http/Requests/PreviewFooterRequest.php',
            $root.'/src/Http/Requests/UpdateFooterRequest.php',
            $root.'/resources/views/admin/footer/index.blade.php',
            $root.'/resources/lang/en/footer.php',
            $root.'/resources/lang/th/footer.php',
            dirname(__DIR__, 3).'/resources/views/components/storefront/layout/partials/site-footer.blade.php',
            dirname(__DIR__, 3).'/resources/css/storefront/footer.css',
        ];
    }

    private function settingsRoot(): string
    {
        return dirname(__DIR__, 3).'/modules/Settings';
    }
}
