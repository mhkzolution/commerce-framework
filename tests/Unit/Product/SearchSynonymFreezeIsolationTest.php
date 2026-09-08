<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class SearchSynonymFreezeIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORGET_NEEDLES = [
        'forgetInstance(SearchSynonymExpander',
        'forgetInstance(\\Commerce\\Product\\Services\\SearchSynonymExpander',
        "forgetInstance('Commerce\\Product\\Services\\SearchSynonymExpander",
        'forgetInstance("Commerce\\Product\\Services\\SearchSynonymExpander',
    ];

    public function test_production_product_php_does_not_forget_the_expander(): void
    {
        $hits = [];

        foreach ($this->productProductionPhp() as $path) {
            $contents = file_get_contents($path);
            $this->assertNotFalse($contents, $path);

            foreach (self::FORGET_NEEDLES as $needle) {
                if (str_contains($contents, $needle)) {
                    $hits[] = $path.' contains '.$needle;
                }
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_expander_constructor_does_not_read_cache_or_config(): void
    {
        $path = $this->repoRoot().'/modules/Product/src/Services/SearchSynonymExpander.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        foreach (['Cache::', 'config(', 'Redis', 'Cache::remember'] as $needle) {
            $this->assertStringNotContainsString($needle, $contents);
        }

        $this->assertStringContainsString("SearchSynonym::query()", $contents);
        $this->assertStringContainsString("pluck('to_term', 'from_term')", $contents);
    }

    public function test_product_production_php_does_not_import_octane(): void
    {
        $hits = [];

        foreach ($this->productProductionPhp() as $path) {
            $contents = file_get_contents($path);
            $this->assertNotFalse($contents, $path);

            if (preg_match('/^use Laravel\\\\Octane\\\\/m', $contents) === 1
                || str_contains($contents, 'use Laravel\\Octane\\')) {
                $hits[] = $path;
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_worker_starting_warm_is_gated_by_class_exists_string(): void
    {
        $path = $this->repoRoot().'/modules/Product/src/ProductServiceProvider.php';
        $contents = file_get_contents($path);
        $this->assertNotFalse($contents);

        $this->assertStringContainsString(
            "class_exists('Laravel\\\\Octane\\\\Events\\\\WorkerStarting')",
            $contents,
        );
        $this->assertStringContainsString(
            "Event::listen('Laravel\\\\Octane\\\\Events\\\\WorkerStarting'",
            $contents,
        );
        $this->assertStringContainsString(
            'make(SearchSynonymExpander::class)',
            $contents,
        );
        $this->assertSame(1, substr_count($contents, 'make(SearchSynonymExpander::class)'));
        $this->assertStringNotContainsString('app(SearchSynonymExpander::class)', $contents);
        $this->assertLessThan(
            strpos($contents, 'make(SearchSynonymExpander::class)'),
            strpos($contents, "class_exists('Laravel\\\\Octane\\\\Events\\\\WorkerStarting')"),
        );
        $this->assertStringNotContainsString(
            'use Laravel\\Octane\\',
            $contents,
        );
    }

    public function test_composer_does_not_require_octane(): void
    {
        foreach ([
            $this->repoRoot().'/composer.json',
            $this->repoRoot().'/composer.lock',
            $this->repoRoot().'/modules/Product/composer.json',
        ] as $path) {
            $contents = file_get_contents($path);
            $this->assertNotFalse($contents, $path);
            $this->assertStringNotContainsString('laravel/octane', $contents, $path);
        }
    }

    /**
     * @return list<string>
     */
    private function productProductionPhp(): array
    {
        $files = [];
        $root = $this->repoRoot().'/modules/Product';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();

            if (str_contains($path, '/tests/')) {
                continue;
            }

            $files[] = $path;
        }

        return $files;
    }

    private function repoRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
