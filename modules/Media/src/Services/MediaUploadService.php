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
use Commerce\Media\Support\MediaTypeResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class MediaUploadService extends BaseService implements MediaUploadServiceInterface
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {}

    public function upload(mixed $file, ?string $folderUuid = null): object
    {
        if (! $file instanceof UploadedFile) {
            throw new DomainException('Only uploaded files are supported in this phase.');
        }

        $mimeType = (string) $file->getMimeType();
        $allowed = config('media.allowed_mimes', []);

        if ($allowed !== [] && ! in_array($mimeType, $allowed, true)) {
            throw new DomainException("File type [{$mimeType}] is not allowed.");
        }

        $disk = (string) config('media.disk', 'public');
        $uuid = (string) Str::uuid();
        $extension = $file->getClientOriginalExtension() ?: $file->extension();
        $filename = $uuid . ($extension ? '.' . $extension : '');
        $path = trim((string) config('media.path', 'media'), '/') . '/' . $filename;

        Storage::disk($disk)->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        [$width, $height] = $this->resolveImageDimensions($file, $mimeType);

        $folderId = null;

        if ($folderUuid !== null) {
            $folderId = MediaFolder::query()->where('uuid', $folderUuid)->value('id');
        }

        $media = Media::query()->create([
            'uuid' => $uuid,
            'folder_id' => $folderId,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'media_type' => MediaTypeResolver::fromMime($mimeType),
            'size' => (int) $file->getSize(),
            'disk' => $disk,
            'path' => $path,
            'width' => $width,
            'height' => $height,
        ]);

        $this->eventBus->dispatch(new MediaUploaded(
            mediaUuid: $media->uuid,
            mimeType: $media->mime_type,
            size: $media->size,
        ));

        return $media;
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function resolveImageDimensions(UploadedFile $file, string $mimeType): array
    {
        if (! str_starts_with($mimeType, 'image/') || $mimeType === 'image/svg+xml') {
            return [null, null];
        }

        $size = @getimagesize($file->getRealPath() ?: '');

        if ($size === false) {
            return [null, null];
        }

        return [$size[0] ?? null, $size[1] ?? null];
    }
}
