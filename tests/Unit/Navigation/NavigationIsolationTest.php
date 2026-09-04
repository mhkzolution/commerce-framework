<?php

declare(strict_types=1);

namespace Tests\Unit\Navigation;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionNamedType;
use SplFileInfo;

final class NavigationIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN = [
        'StorefrontNavigationConfig',
        'SiteIdentityServiceInterface',
        'SiteIdentityService',
        'WebsiteSettingsService',
        'AppearanceController',
        'CustomerExperienceController',
        'CustomerExperienceConfig',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_CONTRACTS = [
        'packages/commerce/contracts/src/Navigation/NavigationQueryServiceInterface.php',
        'packages/commerce/contracts/src/Navigation/NavigationLinkData.php',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_MODULE_FILES = [
        'module.json',
        'src/NavigationServiceProvider.php',
        'src/Services/NavigationQueryService.php',
        'src/Models/NavigationMenu.php',
        'src/Models/NavigationMenuItem.php',
        'src/Http/Controllers/Admin/NavigationMenuController.php',
    ];

    public function test_contract_and_module_surfaces_exist(): void
    {
        foreach (self::REQUIRED_CONTRACTS as $relative) {
            $this->assertFileExists($this->repoRoot().'/'.$relative);
        }

        $this->assertDirectoryExists($this->moduleRoot());

        foreach (self::REQUIRED_MODULE_FILES as $relative) {
            $this->assertFileExists($this->moduleRoot().'/'.$relative);
        }
    }

    public function test_navigation_module_has_no_forbidden_archive_types(): void
    {
        $this->assertDirectoryExists($this->moduleRoot());

        $hits = [];

        foreach ($this->moduleFiles() as $file) {
            $contents = file_get_contents($file->getPathname());
            $this->assertNotFalse($contents, $file->getPathname());

            foreach (self::FORBIDDEN as $token) {
                if (str_contains($contents, $token)) {
                    $hits[] = $file->getPathname().' contains '.$token;
                }
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_footer_navigation_query_delegates_to_contract_without_eloquent(): void
    {
        $path = $this->settingsRoot().'/src/Services/FooterNavigationQuery.php';
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        foreach (self::FORBIDDEN as $token) {
            $this->assertStringNotContainsString($token, $contents);
        }

        $this->assertStringContainsString('NavigationQueryServiceInterface', $contents);
        $this->assertStringNotContainsString('Commerce\\Navigation\\Models', $contents);
        $this->assertStringNotContainsString('Eloquent', $contents);

        $class = 'Commerce\\Settings\\Services\\FooterNavigationQuery';
        $this->assertTrue(class_exists($class), $class.' must exist');

        foreach ((new ReflectionClass($class))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $name = $type->getName();
            $this->assertSame('Commerce\\Contracts\\Navigation\\NavigationQueryServiceInterface', $name);
            $this->assertTrue($type->allowsNull(), 'FooterNavigationQuery must fail-soft when Navigation is unbound');
        }
    }

    public function test_homepage_navigation_query_stays_on_catalog_categories(): void
    {
        $path = $this->repoRoot().'/modules/Cart/src/Services/HomepageNavigationQuery.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $this->assertStringContainsString('Category', $contents);
        $this->assertStringNotContainsString('NavigationQueryServiceInterface', $contents);
        $this->assertStringNotContainsString('Commerce\\Navigation\\', $contents);
        $this->assertStringNotContainsString('NavigationLinkData', $contents);
        $this->assertStringNotContainsString('links(', $contents);
    }

    public function test_admin_website_navigation_link_belongs_to_navigation_module(): void
    {
        $path = $this->repoRoot().'/config/admin.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $this->assertStringContainsString("'route' => 'admin.navigation.show'", $contents);
        $this->assertStringContainsString("'permission' => 'navigation.menu.view'", $contents);
        $this->assertStringContainsString("'module' => 'navigation'", $contents);
        $this->assertStringContainsString("'route' => 'admin.storefront.navigation.show'", $contents);
        $this->assertTrue(
            strpos($contents, "'route' => 'admin.navigation.show'") < strpos($contents, "'route' => 'admin.storefront.navigation.show'"),
            'Website → Navigation must remain the Navigation module; storefront promo navigation is additional.',
        );
    }

    public function test_footer_renderer_and_dtos_do_not_import_navigation(): void
    {
        $hits = [];
        $forbidden = [
            'NavigationQueryServiceInterface',
            'Commerce\\Navigation\\',
            'NavigationLinkData',
            'StorefrontNavigationConfig',
        ];

        foreach ([
            $this->repoRoot().'/resources/views/components/storefront/layout/partials/site-footer.blade.php',
            $this->settingsRoot().'/src/Footer/DTO/FooterPageData.php',
            $this->settingsRoot().'/src/Footer/DTO/FooterSectionData.php',
            $this->settingsRoot().'/src/Footer/DTO/FooterBrandData.php',
            $this->settingsRoot().'/src/Footer/DTO/FooterLinkData.php',
            $this->settingsRoot().'/src/Footer/Drivers/NavigationSectionDriver.php',
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

    public function test_v1_has_no_nested_menu_parent_id(): void
    {
        $this->assertDirectoryExists($this->moduleRoot());

        $hits = [];

        foreach ($this->moduleFiles() as $file) {
            if (! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            $this->assertNotFalse($contents, $file->getPathname());

            if (str_contains($contents, 'parent_id')) {
                $hits[] = $file->getPathname();
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    /**
     * @return list<SplFileInfo>
     */
    private function moduleFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->moduleRoot(), RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $files[] = $file;
        }

        $this->assertNotEmpty($files);

        return $files;
    }

    private function moduleRoot(): string
    {
        return $this->repoRoot().'/modules/Navigation';
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
