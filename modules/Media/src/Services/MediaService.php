<?php

declare(strict_types=1);

namespace Commerce\Media\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Media\Contracts\MediaServiceInterface;
use Commerce\Media\DTO\UpdateMediaData;
use Commerce\Media\Models\Media;
use Commerce\Media\Models\MediaTag;
use Commerce\Media\Support\ImageVariantGenerator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class MediaService extends BaseService implements MediaServiceInterface
{
    public function __construct(
        private readonly MediaUsageService $usageService,
        private readonly ImageVariantGenerator $variantGenerator,
    ) {}

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

        if ($data->caption !== null) {
            $attributes['caption'] = $data->caption;
        }

        if ($data->description !== null) {
            $attributes['description'] = $data->description;
        }

        if ($data->syncFolder) {
            $attributes['folder_id'] = $data->folderId;
        }

        if ($data->crop !== null) {
            $meta = is_array($media->meta) ? $media->meta : [];
            $meta['crop'] = $data->crop;
            $attributes['meta'] = $meta;
        }

        if ($attributes !== []) {
            $media->update($attributes);
        }

        if ($data->tags !== null) {
            $this->syncTags($media, $data->tags);
        }

        $media = $media->fresh(['folder', 'variants', 'tags']);

        if ($data->crop !== null) {
            $this->variantGenerator->generate($media, true);
            $media = $media->fresh(['folder', 'variants', 'tags']);
        }

        return $media;
    }

    public function delete(string $uuid, bool $force = false): void
    {
        $media = Media::query()->with('variants')->where('uuid', $uuid)->first();

        if ($media === null) {
            throw new EntityNotFoundException("Media [{$uuid}] not found.");
        }

        $usages = $this->usageService->forUuid($uuid);

        if ($usages !== [] && ! $force) {
            throw new DomainException('This file is in use and cannot be deleted.');
        }

        Storage::disk($media->disk)->delete($media->path);

        foreach ($media->variants as $variant) {
            Storage::disk($media->disk)->delete($variant->path);
        }

        $media->delete();
    }

    public function deleteMany(array $uuids, bool $force = false): int
    {
        $deleted = 0;

        foreach (array_values(array_unique($uuids)) as $uuid) {
            try {
                $this->delete($uuid, $force);
                $deleted++;
            } catch (EntityNotFoundException) {
                continue;
            }
        }

        return $deleted;
    }

    public function moveMany(array $uuids, ?int $folderId): int
    {
        $uuids = array_values(array_unique($uuids));

        if ($uuids === []) {
            return 0;
        }

        return Media::query()
            ->whereIn('uuid', $uuids)
            ->update(['folder_id' => $folderId]);
    }

    public function tagMany(array $uuids, array $tags): int
    {
        $mediaItems = Media::query()->whereIn('uuid', $uuids)->get();

        foreach ($mediaItems as $media) {
            $existing = $media->tags()->pluck('name')->all();
            $this->syncTags($media, array_values(array_unique([...$existing, ...$tags])));
        }

        return $mediaItems->count();
    }

    public function regenerateMany(array $uuids): int
    {
        $count = 0;

        foreach (Media::query()->with('variants')->whereIn('uuid', $uuids)->get() as $media) {
            $this->variantGenerator->generate($media, true);
            $count++;
        }

        return $count;
    }

    /**
     * @param  list<string>  $names
     */
    private function syncTags(Media $media, array $names): void
    {
        $ids = [];

        foreach ($names as $name) {
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            $tag = MediaTag::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'uuid' => (string) Str::uuid()],
            );

            $ids[] = $tag->id;
        }

        $media->tags()->sync($ids);
    }
}
