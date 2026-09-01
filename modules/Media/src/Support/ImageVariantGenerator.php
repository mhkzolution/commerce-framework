<?php

declare(strict_types=1);

namespace Commerce\Media\Support;

use Commerce\Media\Models\Media;
use Commerce\Media\Models\MediaVariant;
use Illuminate\Contracts\Filesystem\Filesystem;
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
        $sourcePath = $this->resolveReadablePath($disk, $media->path);

        if ($sourcePath === null) {
            return;
        }

        $cleanup = str_starts_with($sourcePath, sys_get_temp_dir());

        try {
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

                $variantFilename = $media->uuid.'-'.$name.'.jpg';
                $variantPath = trim((string) config('media.path', 'media'), '/').'/variants/'.$variantFilename;
                $contents = $this->encodeJpeg($canvas);
                imagedestroy($canvas);

                if ($contents === null) {
                    continue;
                }

                $disk->put($variantPath, $contents);

                MediaVariant::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'media_id' => $media->id,
                    'name' => $name,
                    'path' => $variantPath,
                    'width' => $width,
                    'height' => $height,
                    'size' => strlen($contents),
                ]);
            }

            imagedestroy($source);
        } finally {
            if ($cleanup && is_file($sourcePath)) {
                @unlink($sourcePath);
            }
        }
    }

    private function resolveReadablePath(Filesystem $disk, string $path): ?string
    {
        if (method_exists($disk, 'path')) {
            $localPath = $disk->path($path);

            if (is_file($localPath)) {
                return $localPath;
            }
        }

        if (! $disk->exists($path)) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'media_src_');

        if ($tempPath === false) {
            return null;
        }

        file_put_contents($tempPath, $disk->get($path));

        return $tempPath;
    }

    private function encodeJpeg(\GdImage $canvas): ?string
    {
        ob_start();
        $result = imagejpeg($canvas, null, 85);
        $contents = ob_get_clean();

        if ($result === false || ! is_string($contents)) {
            return null;
        }

        return $contents;
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
