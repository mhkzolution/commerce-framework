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
    public function findByUuid(string $uuid): ?object
    {
        return Media::query()->with(['variants', 'folder'])->where('uuid', $uuid)->first();
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
                $query->where(function ($inner) use ($search): void {
                    $inner->where('original_filename', 'like', "%{$search}%")
                        ->orWhere('alt_text', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(perPage: $perPage, page: $page);
    }
}
