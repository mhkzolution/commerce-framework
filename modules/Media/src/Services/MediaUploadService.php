<?php

declare(strict_types=1);

namespace Commerce\Media\Services;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Contracts\Media\MediaUploadServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Media\Events\MediaUploaded;
use Commerce\Media\Models\Media;
use Commerce\Media\Models\MediaFolder;
use Commerce\Media\Support\ImageVariantGenerator;
use Commerce\Media\Support\MediaTypeResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class MediaUploadService extends BaseService implements MediaUploadServiceInterface
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {}

    public function upload(mixed $file, ?string $folderUuid = null): object
    {
        if (is_string($file)) {
            return $this->importFromUrl($file, $folderUuid);
        }

        if (! $file instanceof UploadedFile) {
            throw new DomainException('Upload requires an uploaded file or URL string.');
        }

        $mimeType = (string) $file->getMimeType();
        $this->assertAllowedMime($mimeType);

        $contents = (string) file_get_contents($file->getRealPath() ?: '');

        return $this->persistMedia(
            contents: $contents,
            mimeType: $mimeType,
            originalFilename: $file->getClientOriginalName(),
            folderUuid: $folderUuid,
            uploadedFile: $file,
        );
    }

    public function replace(string $uuid, mixed $file): object
    {
        if (! $file instanceof UploadedFile) {
            throw new DomainException('Replacement requires an uploaded file.');
        }

        $media = Media::query()->where('uuid', $uuid)->first();

        if ($media === null) {
            throw new DomainException("Media [{$uuid}] not found.");
        }

        $mimeType = (string) $file->getMimeType();
        $this->assertAllowedMime($mimeType);

        $contents = (string) file_get_contents($file->getRealPath() ?: '');
        $disk = Storage::disk($media->disk);
        $extension = $file->getClientOriginalExtension()
            ?: $this->extensionFromMime($mimeType)
            ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)
            ?: 'bin';
        $filename = $media->uuid.($extension ? '.'.$extension : '');
        $path = trim((string) config('media.path', 'media'), '/').'/'.$filename;

        if ($media->path !== $path && $disk->exists($media->path)) {
            $disk->delete($media->path);
        }

        $disk->put($path, $contents);
        [$width, $height] = $this->resolveImageDimensions($contents, $mimeType, $file);

        $media->update([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'media_type' => MediaTypeResolver::fromMime($mimeType),
            'size' => strlen($contents),
            'path' => $path,
            'width' => $width,
            'height' => $height,
        ]);

        $media = $media->fresh(['variants', 'folder', 'tags']);
        app(ImageVariantGenerator::class)->generate($media, true);

        return $media->fresh(['folder', 'variants', 'tags']);
    }

    public function importFromUrl(string $url, ?string $folderUuid = null): Media
    {
        $url = trim($url);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new DomainException('A valid URL is required.');
        }

        $response = Http::timeout(30)
            ->withHeaders(['User-Agent' => 'CommerceFramework/MediaImporter'])
            ->get($url);

        if (! $response->successful()) {
            throw new DomainException('Could not download file from URL.');
        }

        $body = $response->body();
        $maxBytes = (int) config('media.max_upload_size', 10240) * 1024;

        if (strlen($body) > $maxBytes) {
            throw new DomainException('Remote file exceeds maximum upload size.');
        }

        $mimeType = $this->resolveMimeType(
            (string) ($response->header('Content-Type') ?? ''),
            $url,
            $body,
        );

        $this->assertAllowedMime($mimeType);

        $extension = $this->extensionFromMime($mimeType)
            ?? pathinfo((string) (parse_url($url, PHP_URL_PATH) ?? ''), PATHINFO_EXTENSION)
            ?: 'bin';
        $originalFilename = basename((string) (parse_url($url, PHP_URL_PATH) ?: 'imported-file.'.$extension));

        return $this->persistMedia(
            contents: $body,
            mimeType: $mimeType,
            originalFilename: $originalFilename !== '' ? $originalFilename : 'imported-file.'.$extension,
            folderUuid: $folderUuid,
            sourceUrl: $url,
        );
    }

    private function persistMedia(
        string $contents,
        string $mimeType,
        string $originalFilename,
        ?string $folderUuid,
        ?UploadedFile $uploadedFile = null,
        ?string $sourceUrl = null,
    ): Media {
        $disk = (string) config('media.disk', 'public');
        $uuid = (string) Str::uuid();
        $extension = $uploadedFile?->getClientOriginalExtension()
            ?: $this->extensionFromMime($mimeType)
            ?: pathinfo($originalFilename, PATHINFO_EXTENSION)
            ?: 'bin';
        $filename = $uuid.($extension ? '.'.$extension : '');
        $path = trim((string) config('media.path', 'media'), '/').'/'.$filename;

        Storage::disk($disk)->put($path, $contents);

        [$width, $height] = $this->resolveImageDimensions($contents, $mimeType, $uploadedFile);

        $folderId = null;

        if ($folderUuid !== null) {
            $folderId = MediaFolder::query()->where('uuid', $folderUuid)->value('id');
        }

        $media = Media::query()->create([
            'uuid' => $uuid,
            'folder_id' => $folderId,
            'filename' => $filename,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'media_type' => MediaTypeResolver::fromMime($mimeType),
            'size' => strlen($contents),
            'disk' => $disk,
            'path' => $path,
            'width' => $width,
            'height' => $height,
            'meta' => array_filter([
                'source_url' => $sourceUrl,
            ]),
        ]);

        $this->eventBus->dispatch(new MediaUploaded(
            mediaUuid: $media->uuid,
            mimeType: $media->mime_type,
            size: $media->size,
        ));

        return $media;
    }

    private function assertAllowedMime(string $mimeType): void
    {
        $allowed = config('media.allowed_mimes', []);

        if ($allowed !== [] && ! in_array($mimeType, $allowed, true)) {
            throw new DomainException("File type [{$mimeType}] is not allowed.");
        }
    }

    private function resolveMimeType(string $header, string $url, string $body): string
    {
        $mimeType = trim(strtok($header, ';') ?: '');

        if ($mimeType !== '' && $mimeType !== 'application/octet-stream') {
            return $mimeType;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($body);

        if (is_string($detected) && $detected !== '') {
            return $detected;
        }

        $extension = strtolower((string) pathinfo((string) (parse_url($url, PHP_URL_PATH) ?: ''), PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    private function extensionFromMime(string $mimeType): ?string
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            default => null,
        };
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function resolveImageDimensions(string $contents, string $mimeType, ?UploadedFile $file = null): array
    {
        if (! str_starts_with($mimeType, 'image/') || $mimeType === 'image/svg+xml') {
            return [null, null];
        }

        if ($file !== null) {
            $size = @getimagesize($file->getRealPath() ?: '');

            if ($size !== false) {
                return [$size[0] ?? null, $size[1] ?? null];
            }
        }

        $size = @getimagesizefromstring($contents);

        if ($size === false) {
            return [null, null];
        }

        return [$size[0] ?? null, $size[1] ?? null];
    }
}
