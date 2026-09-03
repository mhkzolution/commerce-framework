<?php

declare(strict_types=1);

namespace Tests\Unit\Barcode;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class BarcodeIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const MODULE_FORBIDDEN = [
        'ProductImageResolver',
        'SiteIdentityServiceInterface',
        'App\\Models\\Product',
        'Commerce\\Product\\Services',
        'Commerce\\Inventory',
        'Commerce\\Pos',
        'WarehouseScanner',
    ];

    /**
     * @var list<string>
     */
    private const PRINT_PATH_FILES = [
        'src/Services/BarcodePrintService.php',
        'src/Services/BarcodeLayoutCalculator.php',
        'src/DTO/ResolvedBarcodeLayout.php',
    ];

    /**
     * @var list<string>
     */
    private const PRINT_PATH_FORBIDDEN = [
        'Commerce\\Product',
        'Product::',
    ];

    public function test_barcode_module_source_has_no_forbidden_adapter_leaks(): void
    {
        $hits = [];

        foreach ($this->moduleFiles() as $file) {
            $contents = file_get_contents($file->getPathname());
            $this->assertNotFalse($contents, $file->getPathname());

            foreach (self::MODULE_FORBIDDEN as $token) {
                if (str_contains($contents, $token)) {
                    $hits[] = $file->getPathname().' contains '.$token;
                }
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_print_path_does_not_query_product(): void
    {
        $hits = [];

        foreach (self::PRINT_PATH_FILES as $relative) {
            $path = $this->moduleRoot().'/'.$relative;
            $this->assertFileExists($path);

            $contents = file_get_contents($path);
            $this->assertNotFalse($contents, $path);

            foreach (self::PRINT_PATH_FORBIDDEN as $token) {
                if (str_contains($contents, $token)) {
                    $hits[] = $path.' contains '.$token;
                }
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_search_keeps_barcode_image_resolver(): void
    {
        $this->assertFileExists($this->moduleRoot().'/src/Services/BarcodeImageResolver.php');
        $this->assertFileExists($this->moduleRoot().'/src/Services/BarcodeProductSearchService.php');

        $search = file_get_contents($this->moduleRoot().'/src/Services/BarcodeProductSearchService.php');
        $this->assertNotFalse($search);
        $this->assertStringContainsString('BarcodeImageResolver', $search);
        $this->assertStringNotContainsString('ProductImageResolver', $search);
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
        return dirname(__DIR__, 3).'/modules/Barcode';
    }
}
