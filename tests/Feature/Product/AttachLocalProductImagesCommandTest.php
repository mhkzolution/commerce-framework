<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Media\Models\Media;
use Commerce\Product\Models\ProductMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class AttachLocalProductImagesCommandTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    private string $sourceDir;

    private string $mappingPath;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['media.disk' => 'public']);

        $this->sourceDir = sys_get_temp_dir().'/ppk-uploads-'.uniqid('', true);
        mkdir($this->sourceDir.'/2021/03', 0777, true);
        $this->mappingPath = sys_get_temp_dir().'/ppk-map-'.uniqid('', true).'.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->mappingPath);
        $this->deleteDirectory($this->sourceDir);
        parent::tearDown();
    }

    public function test_dry_run_does_not_create_media_or_product_media(): void
    {
        $this->createPurchasableProduct(sku: 'IMG-DRY-1');
        $this->writeJpeg('2021/03/dry.jpg');
        $this->writeJsonMapping(['IMG-DRY-1' => ['2021/03/dry.jpg']]);

        $this->artisan('product:attach-local-images', [
            '--mapping' => $this->mappingPath,
            '--source' => $this->sourceDir,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame(0, Media::query()->count());
        $this->assertSame(0, ProductMedia::query()->count());
    }

    public function test_attaches_first_file_as_primary_and_preserves_gallery_order(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'IMG-ORD-1');
        $this->writeJpeg('2021/03/first.jpg');
        $this->writeJpeg('2021/03/second.jpg');
        $this->writeJpeg('2021/03/third.jpg');
        $this->writeJsonMapping([
            'IMG-ORD-1' => [
                'uploads/2021/03/first.jpg',
                'uploads/2021/03/second.jpg',
                'uploads/2021/03/third.jpg',
            ],
        ]);

        $this->artisan('product:attach-local-images', [
            '--mapping' => $this->mappingPath,
            '--source' => $this->sourceDir,
        ])->assertSuccessful();

        $rows = ProductMedia::query()
            ->where('product_id', $variant->product_id)
            ->orderBy('position')
            ->get();

        $this->assertCount(3, $rows);
        $this->assertTrue((bool) $rows[0]->is_primary);
        $this->assertFalse((bool) $rows[1]->is_primary);
        $this->assertFalse((bool) $rows[2]->is_primary);
        $this->assertSame([0, 1, 2], $rows->pluck('position')->all());

        $filenames = $rows->map(function (ProductMedia $row): string {
            return (string) Media::query()->where('uuid', $row->media_uuid)->value('original_filename');
        })->all();

        $this->assertSame(['first.jpg', 'second.jpg', 'third.jpg'], $filenames);
        $this->assertSame('ppk-images', Media::query()->first()?->meta['migration'] ?? null);
        $this->assertSame('2021/03/first.jpg', Media::query()->where('original_filename', 'first.jpg')->value('meta')['wordpress_path'] ?? null);
    }

    public function test_dedupes_duplicate_paths_on_the_same_sku(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'IMG-DUP-1');
        $this->writeJpeg('2021/03/same.jpg');
        $this->writeJpeg('2021/03/other.jpg');
        $this->writeJsonMapping([
            'IMG-DUP-1' => [
                '2021/03/same.jpg',
                '2021/03/same.jpg',
                '2021/03/other.jpg',
            ],
        ]);

        $this->artisan('product:attach-local-images', [
            '--mapping' => $this->mappingPath,
            '--source' => $this->sourceDir,
        ])->assertSuccessful();

        $this->assertSame(2, ProductMedia::query()->where('product_id', $variant->product_id)->count());
        $this->assertSame(2, Media::query()->count());
    }

    public function test_reuses_one_media_row_when_two_skus_share_a_file(): void
    {
        $first = $this->createPurchasableProduct(sku: 'IMG-SHARE-A');
        $second = $this->createPurchasableProduct(sku: 'IMG-SHARE-B');
        $this->writeJpeg('2021/03/shared.jpg');
        $this->writeJpeg('2021/03/only-b.jpg');
        $this->writeJsonMapping([
            'IMG-SHARE-A' => ['2021/03/shared.jpg'],
            'IMG-SHARE-B' => ['2021/03/shared.jpg', '2021/03/only-b.jpg'],
        ]);

        $this->artisan('product:attach-local-images', [
            '--mapping' => $this->mappingPath,
            '--source' => $this->sourceDir,
        ])->assertSuccessful();

        $this->assertSame(2, Media::query()->count());
        $sharedUuid = Media::query()->where('original_filename', 'shared.jpg')->value('uuid');
        $this->assertNotNull($sharedUuid);
        $this->assertSame(1, ProductMedia::query()->where('product_id', $first->product_id)->where('media_uuid', $sharedUuid)->where('is_primary', true)->count());
        $this->assertSame(1, ProductMedia::query()->where('product_id', $second->product_id)->where('media_uuid', $sharedUuid)->where('is_primary', true)->count());
        $this->assertSame(2, ProductMedia::query()->where('product_id', $second->product_id)->count());
    }

    public function test_skip_existing_does_not_replace_product_media(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'IMG-SKIP-1');
        $this->writeJpeg('2021/03/new.jpg');
        $this->writeJsonMapping(['IMG-SKIP-1' => ['2021/03/new.jpg']]);

        ProductMedia::query()->create([
            'product_id' => $variant->product_id,
            'media_uuid' => '11111111-1111-1111-1111-111111111111',
            'position' => 0,
            'is_primary' => true,
        ]);

        $this->artisan('product:attach-local-images', [
            '--mapping' => $this->mappingPath,
            '--source' => $this->sourceDir,
        ])->assertSuccessful();

        $this->assertSame(0, Media::query()->count());
        $this->assertSame(1, ProductMedia::query()->where('product_id', $variant->product_id)->count());
        $this->assertSame('11111111-1111-1111-1111-111111111111', ProductMedia::query()->where('product_id', $variant->product_id)->value('media_uuid'));
    }

    public function test_force_replaces_product_media_for_that_sku(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'IMG-FORCE-1');
        $this->writeJpeg('2021/03/fresh.jpg');
        $this->writeJsonMapping(['IMG-FORCE-1' => ['2021/03/fresh.jpg']]);

        ProductMedia::query()->create([
            'product_id' => $variant->product_id,
            'media_uuid' => '22222222-2222-2222-2222-222222222222',
            'position' => 0,
            'is_primary' => true,
        ]);

        $this->artisan('product:attach-local-images', [
            '--mapping' => $this->mappingPath,
            '--source' => $this->sourceDir,
            '--force' => true,
        ])->assertSuccessful();

        $rows = ProductMedia::query()->where('product_id', $variant->product_id)->get();
        $this->assertCount(1, $rows);
        $this->assertNotSame('22222222-2222-2222-2222-222222222222', $rows[0]->media_uuid);
        $this->assertTrue((bool) $rows[0]->is_primary);
        $this->assertSame(1, Media::query()->count());
    }

    public function test_parses_mapping_report_markdown_table(): void
    {
        $variant = $this->createPurchasableProduct(sku: '300058');
        $this->writeJpeg('2021/03/Image-from-iOS-187.jpg');
        $markdown = <<<'MD'
# Product image mapping report

| SKU | Name | Proposed files |
| --- | --- | --- |
| `300058` | Car Body suit | `uploads/2021/03/Image-from-iOS-187.jpg` |
MD;
        $path = sys_get_temp_dir().'/ppk-map-'.uniqid('', true).'.md';
        file_put_contents($path, $markdown);

        try {
            $this->artisan('product:attach-local-images', [
                '--mapping' => $path,
                '--source' => $this->sourceDir,
            ])->assertSuccessful();
        } finally {
            @unlink($path);
        }

        $this->assertSame(1, ProductMedia::query()->where('product_id', $variant->product_id)->count());
    }

    public function test_continues_when_sku_or_file_is_missing(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'IMG-OK-1');
        $this->writeJpeg('2021/03/ok.jpg');
        $this->writeJsonMapping([
            'IMG-MISSING-SKU' => ['2021/03/ok.jpg'],
            'IMG-OK-1' => ['2021/03/missing.jpg', '2021/03/ok.jpg'],
        ]);

        $this->artisan('product:attach-local-images', [
            '--mapping' => $this->mappingPath,
            '--source' => $this->sourceDir,
        ])->assertSuccessful();

        $rows = ProductMedia::query()->where('product_id', $variant->product_id)->orderBy('position')->get();
        $this->assertCount(1, $rows);
        $this->assertTrue((bool) $rows[0]->is_primary);
        $this->assertSame('ok.jpg', Media::query()->where('uuid', $rows[0]->media_uuid)->value('original_filename'));
    }

    /**
     * @param  array<string, list<string>>  $mapping
     */
    private function writeJsonMapping(array $mapping): void
    {
        file_put_contents($this->mappingPath, json_encode($mapping, JSON_UNESCAPED_SLASHES));
    }

    private function writeJpeg(string $relative): void
    {
        $path = $this->sourceDir.'/'.$relative;
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($path, $this->fakeJpegBytes());
    }

    private function fakeJpegBytes(): string
    {
        return base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAf/CABEIAAEAAQMBIgACEQEDEQH/xAAUAAEAAAAAAAAAAAAAAAAAAAAK/9oACAEBAAAAAH8f/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAhAAAAB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAxAAAAB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwB//9k=') ?: '';
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $directory.'/'.$item;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($directory);
    }
}
