<?php

declare(strict_types=1);

namespace Commerce\Product\Import;

use Commerce\Contracts\Media\MediaUploadServiceInterface;
use Commerce\Media\Models\Media;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Throwable;

final class ProductLocalImageAttacher
{
    public const MIGRATION_KEY = 'ppk-images';

    public function __construct(
        private readonly ProductImageMappingReader $mappingReader,
        private readonly MediaUploadServiceInterface $mediaUploadService,
    ) {}

    /**
     * @return array{
     *     products_attached: int,
     *     products_skipped: int,
     *     missing_products: int,
     *     media_created: int,
     *     media_reused: int,
     *     product_media: int,
     *     missing_files: int,
     *     warnings: int,
     *     errors: int
     * }
     */
    public function attach(
        string $mappingPath,
        string $sourceRoot,
        bool $dryRun = false,
        bool $force = false,
        ?int $limit = null,
        ?string $resumeFromSku = null,
    ): array {
        $stats = [
            'products_attached' => 0,
            'products_skipped' => 0,
            'missing_products' => 0,
            'media_created' => 0,
            'media_reused' => 0,
            'product_media' => 0,
            'missing_files' => 0,
            'warnings' => 0,
            'errors' => 0,
        ];

        $mapping = $this->mappingReader->read($mappingPath);
        $sourceRoot = rtrim($sourceRoot, '/');
        $batch = now()->toIso8601String();

        if ($resumeFromSku !== null && $resumeFromSku !== '') {
            $mapping = $this->resumeFrom($mapping, $resumeFromSku);
        }

        if ($limit !== null) {
            $mapping = array_slice($mapping, 0, $limit, true);
        }

        foreach ($mapping as $sku => $paths) {
            $sku = (string) $sku;
            $product = $this->findProductBySku($sku);

            if ($product === null) {
                $stats['missing_products']++;
                $stats['warnings']++;

                continue;
            }

            if (! $force && $product->media()->exists()) {
                $stats['products_skipped']++;

                continue;
            }

            if ($dryRun) {
                foreach ($paths as $relative) {
                    if (! is_file($sourceRoot.'/'.$relative)) {
                        $stats['missing_files']++;
                        $stats['warnings']++;
                    }
                }
                $stats['products_attached']++;
                $stats['product_media'] += count($paths);

                continue;
            }

            if ($force) {
                $product->media()->delete();
            }

            $attached = 0;
            $position = 0;

            foreach ($paths as $relative) {
                try {
                    $absolute = $sourceRoot.'/'.$relative;

                    if (! is_file($absolute)) {
                        $stats['missing_files']++;
                        $stats['warnings']++;

                        continue;
                    }

                    $media = $this->findExistingMedia($relative);

                    if ($media !== null) {
                        $stats['media_reused']++;
                    } else {
                        $media = $this->ingest($absolute, $relative, $batch);
                        $stats['media_created']++;
                    }

                    ProductMedia::query()->create([
                        'product_id' => $product->id,
                        'media_uuid' => $media->uuid,
                        'position' => $position,
                        'is_primary' => $position === 0,
                    ]);
                    $position++;
                    $attached++;
                    $stats['product_media']++;
                } catch (Throwable) {
                    $stats['errors']++;
                    $stats['warnings']++;
                }
            }

            if ($attached > 0) {
                $stats['products_attached']++;
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, list<string>>  $mapping
     * @return array<string, list<string>>
     */
    private function resumeFrom(array $mapping, string $sku): array
    {
        $started = false;
        $sliced = [];

        foreach ($mapping as $mappedSku => $paths) {
            $mappedSku = (string) $mappedSku;
            if (! $started && $mappedSku !== $sku) {
                continue;
            }

            $started = true;
            $sliced[$mappedSku] = $paths;
        }

        return $sliced;
    }

    private function findProductBySku(string $sku): ?Product
    {
        $variant = ProductVariant::query()
            ->where('sku', $sku)
            ->whereNull('deleted_at')
            ->first();

        $product = $variant?->product;

        if ($product === null || $product->deleted_at !== null) {
            return null;
        }

        return $product;
    }

    private function findExistingMedia(string $wordpressPath): ?Media
    {
        return Media::query()
            ->where('meta->wordpress_path', $wordpressPath)
            ->first();
    }

    private function ingest(string $absolutePath, string $wordpressPath, string $batch): Media
    {
        $mime = mime_content_type($absolutePath) ?: 'image/jpeg';
        $file = new UploadedFile(
            $absolutePath,
            basename($absolutePath),
            $mime,
            null,
            true,
        );

        /** @var Media $media */
        $media = $this->mediaUploadService->upload($file);
        $meta = is_array($media->meta) ? $media->meta : [];
        $meta['wordpress_path'] = $wordpressPath;
        $meta['migration'] = self::MIGRATION_KEY;
        $meta['batch'] = $batch;
        $media->update(['meta' => $meta]);

        return $media->fresh() ?? $media;
    }
}
