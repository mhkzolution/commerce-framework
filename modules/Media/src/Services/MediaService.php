<?php

declare(strict_types=1);

namespace Commerce\Media\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Media\Contracts\MediaServiceInterface;
use Commerce\Media\DTO\UpdateMediaData;
use Commerce\Media\Models\Media;
use Illuminate\Support\Facades\Storage;

final class MediaService extends BaseService implements MediaServiceInterface
{
    public function update(string $uuid, UpdateMediaData $data): Media
    {
        $media = Media::query()->where('uuid', $uuid)->first();

        if ($media === null) {
            throw new EntityNotFoundException("Media [{$uuid}] not found.");
        }

        $attributes = [];

        if ($data->altText !== null) {
            $attributes['alt_text'] = $data->altText;
        }

        if ($data->syncFolder) {
            $attributes['folder_id'] = $data->folderId;
        }

        if ($attributes !== []) {
            $media->update($attributes);
        }

        return $media->fresh();
    }

    public function delete(string $uuid): void
    {
        $media = Media::query()->with('variants')->where('uuid', $uuid)->first();

        if ($media === null) {
            throw new EntityNotFoundException("Media [{$uuid}] not found.");
        }

        Storage::disk($media->disk)->delete($media->path);

        foreach ($media->variants as $variant) {
            Storage::disk($media->disk)->delete($variant->path);
        }

        $media->delete();
    }
}
