<?php

declare(strict_types=1);

namespace Commerce\Media\Services;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Media\Models\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

final class MediaQueryService extends BaseQueryService implements MediaQueryServiceInterface
{
    /** @var array<string, Media|null> */
    private array $mediaCache = [];

    /** @var array<string, string|null> */
    private array $urlCache = [];

    public function findByUuid(string $uuid): ?object
    {
        return $this->resolveMedia($uuid);
    }

    public function getUrl(string $uuid, ?string $variant = null): ?string
    {
        $cacheKey = $uuid.'|'.($variant ?? '');

        if (array_key_exists($cacheKey, $this->urlCache)) {
            return $this->urlCache[$cacheKey];
        }

        $media = $this->resolveMedia($uuid);

        if ($media === null) {
            return $this->urlCache[$cacheKey] = null;
        }

        return $this->urlCache[$cacheKey] = $this->buildUrl($media, $variant);
    }

    /**
     * @param  list<string>  $uuids
     */
    public function preload(array $uuids): void
    {
        $uuids = array_values(array_unique(array_filter($uuids)));

        $missing = array_values(array_filter(
            $uuids,
            fn (string $uuid): bool => ! array_key_exists($uuid, $this->mediaCache),
        ));

        if ($missing === []) {
            return;
        }

        $records = Media::query()
            ->with(['variants', 'folder'])
            ->whereIn('uuid', $missing)
            ->get()
            ->keyBy('uuid');

        foreach ($missing as $uuid) {
            $this->mediaCache[$uuid] = $records->get($uuid);
        }
    }

    public function findByUuids(array $uuids): array
    {
        $this->preload($uuids);

        $result = [];

        foreach ($uuids as $uuid) {
            $media = $this->mediaCache[$uuid] ?? null;

            if ($media instanceof Media) {
                $result[$uuid] = $media;
            }
        }

        return $result;
    }

    /**
     * @return LengthAwarePaginator<int, Media>
     */
    public function paginate(
        ?string $folderUuid = null,
        ?string $search = null,
        int $perPage = 24,
        ?string $type = null,
        ?string $period = null,
        int $page = 1,
    ) {
        $perPage = max(1, min(96, $perPage));
        $page = max(1, $page);

        $query = Media::query()->with(['variants', 'folder']);

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where(function ($inner) use ($term): void {
                $inner->where('original_filename', 'like', "%{$term}%")
                    ->orWhere('filename', 'like', "%{$term}%")
                    ->orWhere('alt_text', 'like', "%{$term}%")
                    ->orWhere('mime_type', 'like', "%{$term}%")
                    ->orWhere('uuid', 'like', "%{$term}%")
                    ->orWhereHas('folder', static function ($folderQuery) use ($term): void {
                        $folderQuery->where('name', 'like', "%{$term}%");
                    });
            });
        }

        if ($folderUuid === 'unfiled') {
            $query->whereNull('folder_id');
        } elseif ($folderUuid !== null && $folderUuid !== '' && $folderUuid !== 'all') {
            $query->whereHas('folder', static fn ($folderQuery) => $folderQuery->where('uuid', $folderUuid));
        }

        match ($type) {
            'images' => $query->where('media_type', 'image')->where('mime_type', '!=', 'image/svg+xml'),
            'pdfs' => $query->where('mime_type', 'application/pdf'),
            'svg' => $query->where('mime_type', 'image/svg+xml'),
            'webp' => $query->where('mime_type', 'image/webp'),
            default => null,
        };

        match ($period) {
            'today' => $query->whereDate('created_at', now()->toDateString()),
            'week' => $query->where('created_at', '>=', now()->subDays(7)),
            'month' => $query->where('created_at', '>=', now()->subDays(30)),
            default => null,
        };

        return $query->latest()->paginate(perPage: $perPage, page: $page);
    }

    /**
     * @return LengthAwarePaginator<int, Media>
     */
    public function picker(?string $search = null, bool $imagesOnly = true, int $perPage = 24, int $page = 1)
    {
        return Media::query()
            ->with('variants')
            ->when($imagesOnly, static fn ($query) => $query->where('media_type', 'image'))
            ->when($search, static function ($query, string $search): void {
                $term = trim($search);
                $query->where(function ($inner) use ($term): void {
                    $inner->where('original_filename', 'like', "%{$term}%")
                        ->orWhere('filename', 'like', "%{$term}%")
                        ->orWhere('alt_text', 'like', "%{$term}%")
                        ->orWhere('mime_type', 'like', "%{$term}%")
                        ->orWhere('uuid', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(perPage: $perPage, page: $page);
    }

    private function resolveMedia(string $uuid): ?Media
    {
        if (array_key_exists($uuid, $this->mediaCache)) {
            $cached = $this->mediaCache[$uuid];

            return $cached instanceof Media ? $cached : null;
        }

        $media = Media::query()->with(['variants', 'folder'])->where('uuid', $uuid)->first();
        $this->mediaCache[$uuid] = $media;

        return $media;
    }

    private function buildUrl(Media $media, ?string $variant): ?string
    {
        if ($variant !== null) {
            $mediaVariant = $media->variants->firstWhere('name', $variant);

            if ($mediaVariant !== null && $mediaVariant->path !== '') {
                return Storage::disk($media->disk)->url($mediaVariant->path);
            }
        }

        if ($media->path !== '') {
            return Storage::disk($media->disk)->url($media->path);
        }

        return null;
    }
}
