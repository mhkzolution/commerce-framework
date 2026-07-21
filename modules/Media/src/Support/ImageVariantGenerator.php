<?php

declare(strict_types=1);

namespace Commerce\Media\Support;

use Commerce\Media\Models\Media;
use Commerce\Media\Models\MediaVariant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ImageVariantGenerator
{
    public function generate(Media $media): void
    {
        if (! $media->isImage() || $media->mime_type === 'image/svg+xml') {
            return;
        }

        $variants = config('media.variants', []);

        if ($variants === []) {
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

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        foreach ($variants as $name => $config) {
            if ($media->variants()->where('name', $name)->exists()) {
                continue;
            }

            $targetWidth = (int) ($config['width'] ?? 150);
            $targetHeight = (int) ($config['height'] ?? 150);
            [$width, $height] = $this->fitWithin($sourceWidth, $sourceHeight, $targetWidth, $targetHeight);

            $canvas = imagecreatetruecolor($width, $height);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);

            imagecopyresampled(
                $canvas,
                $source,
                0,
                0,
                0,
                0,
                $width,
                $height,
                $sourceWidth,
                $sourceHeight,
            );

            $variantFilename = $media->uuid . '-' . $name . '.jpg';
            $variantPath = trim((string) config('media.path', 'media'), '/') . '/variants/' . $variantFilename;
            $absolutePath = $disk->path($variantPath);

            if (! is_dir(dirname($absolutePath))) {
                mkdir(dirname($absolutePath), 0755, true);
            }

            imagejpeg($canvas, $absolutePath, 85);
            imagedestroy($canvas);

            MediaVariant::query()->create([
                'uuid' => (string) Str::uuid(),
                'media_id' => $media->id,
                'name' => $name,
                'path' => $variantPath,
                'width' => $width,
                'height' => $height,
                'size' => is_file($absolutePath) ? filesize($absolutePath) : null,
            ]);
        }

        imagedestroy($source);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function fitWithin(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        $ratio = min($maxWidth / max($width, 1), $maxHeight / max($height, 1), 1);

        return [
            max(1, (int) round($width * $ratio)),
            max(1, (int) round($height * $ratio)),
        ];
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
}
