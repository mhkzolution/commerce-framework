<?php

declare(strict_types=1);

namespace Tests\Unit\Pos;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class PosWarehouseIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN = [
        'SiteIdentityServiceInterface',
        'ProductImageResolver',
        'AppearanceController',
        'CustomerExperienceController',
        'commerce-framework-old',
    ];

    public function test_pos_module_source_has_no_forbidden_adapter_leaks(): void
    {
        $this->assertDirectoryHasNoForbiddenTokens($this->repoRoot().'/modules/Pos');
    }

    public function test_warehouse_scanner_module_source_has_no_forbidden_adapter_leaks(): void
    {
        $this->assertDirectoryHasNoForbiddenTokens($this->repoRoot().'/modules/WarehouseScanner');
    }

    public function test_pos_and_scanner_layouts_use_config_admin_name(): void
    {
        $pos = file_get_contents($this->repoRoot().'/modules/Pos/resources/views/layouts/pos.blade.php');
        $scanner = file_get_contents($this->repoRoot().'/modules/WarehouseScanner/resources/views/layouts/scanner.blade.php');

        $this->assertNotFalse($pos);
        $this->assertNotFalse($scanner);
        $this->assertStringContainsString("config('admin.name'", $pos);
        $this->assertStringContainsString("config('admin.name'", $scanner);
        $this->assertStringNotContainsString('x-site.favicon', $pos);
        $this->assertStringNotContainsString('x-site.fonts', $pos);
        $this->assertStringNotContainsString('x-site.favicon', $scanner);
        $this->assertStringNotContainsString('x-site.fonts', $scanner);
    }

    public function test_pos_image_service_uses_media_query(): void
    {
        $path = $this->repoRoot().'/modules/Pos/src/Services/PosProductImageService.php';
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('MediaQueryServiceInterface', $contents);
        $this->assertStringNotContainsString('ProductImageResolver', $contents);
    }

    public function test_scanner_lookup_uses_barcode_search_result_dto(): void
    {
        $path = $this->repoRoot().'/modules/WarehouseScanner/src/Services/ScannerProductLookupService.php';
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);
        $this->assertStringContainsString('BarcodeSearchResult', $contents);
        $this->assertStringContainsString('toArray()', $contents);
    }

    /**
     * @param  list<string>  $hits
     */
    private function assertDirectoryHasNoForbiddenTokens(string $root): void
    {
        $this->assertDirectoryExists($root);

        $hits = [];

        foreach ($this->phpAndBladeFiles($root) as $file) {
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

    /**
     * @return list<SplFileInfo>
     */
    private function phpAndBladeFiles(string $root): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $ext = $file->getExtension();
            if (! in_array($ext, ['php', 'json', 'js', 'css'], true) && ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $files[] = $file;
        }

        $this->assertNotEmpty($files);

        return $files;
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
