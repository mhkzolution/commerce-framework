<?php

declare(strict_types=1);

namespace Commerce\Media\Services;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Media\Models\Media;
use Illuminate\Support\Facades\Storage;

final class MediaQueryService extends BaseQueryService implements MediaQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object
    {
        return Media::query()->where('uuid', $uuid)->first();
    }

    public function getUrl(string $uuid, ?string $variant = null): ?string
    {
        $media = Media::query()->with('variants')->where('uuid', $uuid)->first();

        if ($media === null) {
            return null;
        }

        if ($variant !== null) {
            $mediaVariant = $media->variants->firstWhere('name', $variant);

            if ($mediaVariant !== null) {
                return Storage::disk($media->disk)->url($mediaVariant->path);
            }
        }

        return Storage::disk($media->disk)->url($media->path);
    }

    public function findByUuids(array $uuids): array
    {
        return Media::query()
            ->whereIn('uuid', $uuids)
            ->get()
            ->keyBy('uuid')
            ->all();
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Media>
     */
    public function paginate(?string $folderUuid = null, ?string $search = null, int $perPage = 24)
    {
        return Media::query()
            ->with(['variants', 'folder'])
            ->when($search, static function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('original_filename', 'like', "%{$search}%")
                        ->orWhere('alt_text', 'like', "%{$search}%");
                });
            }, static function ($query) use ($folderUuid): void {
                if ($folderUuid) {
                    $query->whereHas('folder', static fn ($folderQuery) => $folderQuery->where('uuid', $folderUuid));
                } else {
                    $query->whereNull('folder_id');
                }
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Media>
     */
    public function picker(?string $search = null, bool $imagesOnly = true, int $perPage = 24, int $page = 1)
    {
        return Media::query()
            ->with('variants')
            ->when($imagesOnly, static fn ($query) => $query->where('media_type', 'image'))
            ->when($search, static function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('original_filename', 'like', "%{$search}%")
                        ->orWhere('alt_text', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(perPage: $perPage, page: $page);
    }
}
