<?php

declare(strict_types=1);

namespace Commerce\Media\Support;

use Commerce\Media\Models\Media;
use Commerce\Media\Models\MediaVariant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ImageVariantGenerator
{
    public function generate(Media $media, bool $force = false): void
    {
        if (! $media->isImage() || $media->mime_type === 'image/svg+xml') {
            return;
        }

        if (! function_exists('imagewebp') || ! function_exists('imagecreatetruecolor')) {
            return;
        }

        $variants = config('media.variants', []);

        if (! is_array($variants) || $variants === []) {
            return;
        }

        $disk = Storage::disk($media->disk);
        $sourcePath = $disk->path($media->path);

        if (! is_file($sourcePath)) {
            return;
        }

        $source = $this->loadImage($sourcePath, $media->mime_type);

        if ($source === null) {
            return;
        }

        $source = $this->applyCrop($source, is_array($media->meta) ? ($media->meta['crop'] ?? null) : null);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $format = (string) (config('media.output_format', 'webp') ?: 'webp');

        foreach ($variants as $name => $config) {
            if (! is_string($name) || ! is_array($config)) {
                continue;
            }

            $existing = $media->variants()->where('name', $name)->first();

            if ($existing !== null && ! $force) {
                continue;
            }

            [$width, $height] = $this->targetSize($sourceWidth, $sourceHeight, $config);
            $canvas = $this->resample($source, $sourceWidth, $sourceHeight, $width, $height);

            if ($canvas === null) {
                continue;
            }

            $extension = (string) ($config['format'] ?? $format);
            $variantPath = $this->variantPath($media->uuid, $name, $extension);
            $absolutePath = $disk->path($variantPath);

            if (! is_dir(dirname($absolutePath))) {
                mkdir(dirname($absolutePath), 0755, true);
            }

            $quality = (int) ($config['quality'] ?? 80);
            $written = $this->writeImage($canvas, $absolutePath, $extension, $quality);
            imagedestroy($canvas);

            if (! $written) {
                continue;
            }

            $attributes = [
                'path' => $variantPath,
                'width' => $width,
                'height' => $height,
                'size' => is_file($absolutePath) ? filesize($absolutePath) : null,
            ];

            if ($existing !== null) {
                if ($existing->path !== $variantPath && $disk->exists($existing->path)) {
                    $disk->delete($existing->path);
                }

                $existing->update($attributes);
            } else {
                MediaVariant::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'media_id' => $media->id,
                    'name' => $name,
                    ...$attributes,
                ]);
            }
        }

        imagedestroy($source);
        $media->load('variants');
        $this->applyOriginalPolicy($media);
    }

    private function applyOriginalPolicy(Media $media): void
    {
        if (config('media.keep_original', true)) {
            return;
        }

        $detail = $media->variants->firstWhere('name', 'detail');

        if ($detail === null) {
            return;
        }

        $disk = Storage::disk($media->disk);

        if ($media->path !== $detail->path && $disk->exists($media->path)) {
            $disk->delete($media->path);
        }

        $media->update([
            'path' => $detail->path,
            'filename' => basename($detail->path),
            'mime_type' => 'image/webp',
            'width' => $detail->width,
            'height' => $detail->height,
            'size' => $detail->size,
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: int, 1: int}
     */
    private function targetSize(int $width, int $height, array $config): array
    {
        if (isset($config['max'])) {
            $max = max(1, (int) $config['max']);
            $ratio = min($max / max($width, 1), $max / max($height, 1), 1.0);

            return [
                max(1, (int) round($width * $ratio)),
                max(1, (int) round($height * $ratio)),
            ];
        }

        $targetWidth = max(1, (int) ($config['width'] ?? $width));
        $ratio = min($targetWidth / max($width, 1), 1.0);

        return [
            max(1, (int) round($width * $ratio)),
            max(1, (int) round($height * $ratio)),
        ];
    }

    private function resample(\GdImage $source, int $sourceWidth, int $sourceHeight, int $width, int $height): ?\GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);

        if ($canvas === false) {
            return null;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        return $canvas;
    }

    private function writeImage(\GdImage $canvas, string $absolutePath, string $format, int $quality): bool
    {
        return match ($format) {
            'webp' => imagewebp($canvas, $absolutePath, max(0, min(100, $quality))),
            default => false,
        };
    }

    private function variantPath(string $uuid, string $name, string $extension): string
    {
        $prefix = trim((string) config('media.path', 'media'), '/');

        return $prefix.'/variants/'.$uuid.'-'.$name.'.'.$extension;
    }

    private function loadImage(string $path, string $mimeType): ?\GdImage
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path) ?: null,
            'image/png' => @imagecreatefrompng($path) ?: null,
            'image/gif' => @imagecreatefromgif($path) ?: null,
            'image/webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            default => null,
        };
    }

    private function applyCrop(\GdImage $source, mixed $crop): \GdImage
    {
        if (! is_array($crop)) {
            return $source;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $x = max(0, (int) ($crop['x'] ?? 0));
        $y = max(0, (int) ($crop['y'] ?? 0));
        $width = max(1, (int) ($crop['width'] ?? $sourceWidth));
        $height = max(1, (int) ($crop['height'] ?? $sourceHeight));

        if ($x >= $sourceWidth || $y >= $sourceHeight) {
            return $source;
        }

        $width = min($width, $sourceWidth - $x);
        $height = min($height, $sourceHeight - $y);

        if ($width < 1 || $height < 1 || ($x === 0 && $y === 0 && $width === $sourceWidth && $height === $sourceHeight)) {
            return $source;
        }

        $cropped = imagecrop($source, [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
        ]);

        if ($cropped === false) {
            return $source;
        }

        imagedestroy($source);

        return $cropped;
    }
}
