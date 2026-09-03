<?php

declare(strict_types=1);

namespace Tests\Unit\Cart;

use PHPUnit\Framework\TestCase;

final class HomepageIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const ARCHIVE_FORBIDDEN = [
        'StorefrontNavigationCatalog',
        'StorefrontInStockCatalog',
        'StorefrontProductPageService',
        'ProductImageResolver',
        'SiteIdentityServiceInterface',
    ];

    /**
     * @var list<string>
     */
    private const HOMEPAGE_SERVICE_MODEL_FORBIDDEN = [
        'Commerce\\Catalog\\Models\\Category',
        'Commerce\\Product\\Models\\Product',
        'Commerce\\Contracts\\Inventory',
        'Commerce\\Product\\Services\\ProductQueryService',
        'Commerce\\Product\\Services\\ProductImageResolver',
    ];

    public function test_homepage_adapters_do_not_import_archive_storefront_services(): void
    {
        $hits = [];

        foreach ($this->homepageFiles() as $path) {
            $contents = file_get_contents($path);
            $this->assertNotFalse($contents, $path);

            foreach (self::ARCHIVE_FORBIDDEN as $token) {
                if (str_contains($contents, $token)) {
                    $hits[] = $path.' contains '.$token;
                }
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_home_page_service_does_not_query_product_category_or_inventory(): void
    {
        $path = $this->cartRoot().'/src/Services/StorefrontHomePageService.php';
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $hits = [];
        foreach (self::HOMEPAGE_SERVICE_MODEL_FORBIDDEN as $token) {
            if (str_contains($contents, $token)) {
                $hits[] = $path.' contains '.$token;
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_home_page_service_injects_homepage_query_adapters(): void
    {
        $path = $this->cartRoot().'/src/Services/StorefrontHomePageService.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $this->assertStringContainsString('HomepageNavigationQuery', $contents);
        $this->assertStringContainsString('HomepageProductQuery', $contents);
        $this->assertStringContainsString('HomepageBrandingQuery', $contents);
        $this->assertStringContainsString('HomeContentQueryService', $contents);
        $this->assertStringContainsString('MediaQueryServiceInterface', $contents);
    }

    /**
     * @return list<string>
     */
    private function homepageFiles(): array
    {
        $root = $this->cartRoot();
        $paths = [
            $root.'/src/Services/StorefrontHomePageService.php',
            $root.'/src/Http/Controllers/HomeController.php',
            $root.'/src/Services/HomepageNavigationQuery.php',
            $root.'/src/Services/HomepageProductQuery.php',
            $root.'/src/Services/HomepageBrandingQuery.php',
            $root.'/src/DTO/HomepageNavigationData.php',
            $root.'/src/DTO/HomepageProductCardData.php',
            $root.'/src/DTO/HomepageBrandingData.php',
        ];

        foreach ($paths as $path) {
            $this->assertFileExists($path);
        }

        return $paths;
    }

    private function cartRoot(): string
    {
        return dirname(__DIR__, 3).'/modules/Cart';
    }
}
