<?php

declare(strict_types=1);

namespace Commerce\Media\Services;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Media\Models\Media;
use Commerce\Media\Models\MediaVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

final class MediaQueryService extends BaseQueryService implements MediaQueryServiceInterface
{
    /** @var array<string, Media|null> */
    private array $loaded = [];

    public function findByUuid(string $uuid): ?object
    {
        return $this->media($uuid);
    }

    public function getUrl(string $uuid, ?string $variant = null): ?string
    {
        $media = $this->media($uuid);

        if ($media === null) {
            return null;
        }

        if ($variant !== null) {
            $match = $this->findVariant($media, $variant);

            if ($match !== null) {
                return $this->diskUrl($media, $match->path);
            }
        }

        return $this->diskUrl($media, $media->path);
    }

    public function getSrcset(string $uuid): ?string
    {
        $media = $this->media($uuid);

        if ($media === null) {
            return null;
        }

        $order = config('media.srcset', ['thumbnail', 'card', 'detail']);
        $parts = [];
        $seen = [];

        foreach (is_array($order) ? $order : [] as $name) {
            if (! is_string($name)) {
                continue;
            }

            $variant = $this->findVariant($media, $name);

            if ($variant === null || $variant->width === null || $variant->width < 1) {
                continue;
            }

            if (isset($seen[$variant->width])) {
                continue;
            }

            $seen[$variant->width] = true;
            $url = $this->diskUrl($media, $variant->path);

            if ($url === null) {
                continue;
            }

            $parts[] = $url.' '.$variant->width.'w';
        }

        return $parts === [] ? null : implode(', ', $parts);
    }

    public function findByUuids(array $uuids): array
    {
        $uuids = array_values(array_unique(array_filter($uuids, static fn (mixed $uuid): bool => is_string($uuid) && $uuid !== '')));

        if ($uuids === []) {
            return [];
        }

        $missing = array_values(array_filter($uuids, fn (string $uuid): bool => ! array_key_exists($uuid, $this->loaded)));

        if ($missing !== []) {
            $found = Media::query()
                ->with(['variants', 'tags', 'folder'])
                ->whereIn('uuid', $missing)
                ->get()
                ->keyBy('uuid');

            foreach ($missing as $uuid) {
                $this->loaded[$uuid] = $found->get($uuid);
            }
        }

        $result = [];

        foreach ($uuids as $uuid) {
            $media = $this->loaded[$uuid] ?? null;

            if ($media !== null) {
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
        ?string $size = null,
        ?string $sort = null,
        string $direction = 'desc',
        ?string $tag = null,
    ) {
        $perPage = max(1, min(96, $perPage));
        $page = max(1, $page);

        $query = Media::query()->with(['variants', 'folder', 'tags']);

        $this->applySearch($query, $search);

        if ($folderUuid === 'unfiled') {
            $query->whereNull('folder_id');
        } elseif ($folderUuid !== null && $folderUuid !== '' && $folderUuid !== 'all') {
            $query->whereHas('folder', static fn ($folderQuery) => $folderQuery->where('uuid', $folderUuid));
        }

        match ($type) {
            'images' => $query->where('media_type', 'image')->where('mime_type', '!=', 'image/svg+xml'),
            'videos' => $query->where('media_type', 'video'),
            'documents' => $query->whereIn('media_type', ['document', 'other']),
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

        $this->applySizeFilter($query, $size);

        if ($tag !== null && trim($tag) !== '') {
            $tagTerm = trim($tag);
            $query->whereHas('tags', static function ($tagQuery) use ($tagTerm): void {
                $tagQuery->where('slug', $tagTerm)->orWhere('name', $tagTerm);
            });
        }

        $this->applySort($query, $sort, $direction);

        return $query->paginate(perPage: $perPage, page: $page);
    }

    /**
     * @return LengthAwarePaginator<int, Media>
     */
    public function picker(
        ?string $search = null,
        bool $imagesOnly = true,
        int $perPage = 24,
        int $page = 1,
        ?string $folderUuid = null,
        bool $recent = false,
    ) {
        $query = Media::query()
            ->with(['variants', 'folder', 'tags'])
            ->when($imagesOnly, static fn ($query) => $query->where('media_type', 'image'));

        $this->applySearch($query, $search);

        return $query
            ->when($folderUuid === 'unfiled', static fn ($query) => $query->whereNull('folder_id'))
            ->when(
                is_string($folderUuid) && ! in_array($folderUuid, ['', 'all', 'unfiled'], true),
                static fn ($query) => $query->whereHas('folder', static fn ($folder) => $folder->where('uuid', $folderUuid)),
            )
            ->when($recent, static fn ($query) => $query->where('created_at', '>=', now()->subDays(7)))
            ->latest()
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * @return array{
     *     total: int,
     *     storage_bytes: int,
     *     images: int,
     *     videos: int,
     *     documents: int,
     *     recent: list<Media>
     * }
     */
    public function insights(): array
    {
        $counts = Media::query()
            ->selectRaw('media_type, COUNT(*) as aggregate, COALESCE(SUM(size), 0) as bytes')
            ->groupBy('media_type')
            ->get();

        $byType = $counts->keyBy('media_type');

        return [
            'total' => (int) $counts->sum('aggregate'),
            'storage_bytes' => (int) $counts->sum('bytes'),
            'images' => (int) ($byType->get('image')?->aggregate ?? 0),
            'videos' => (int) ($byType->get('video')?->aggregate ?? 0),
            'documents' => (int) ($byType->get('document')?->aggregate ?? 0) + (int) ($byType->get('other')?->aggregate ?? 0),
            'recent' => Media::query()->with(['variants', 'folder', 'tags'])->latest()->limit(8)->get()->all(),
        ];
    }

    private function media(string $uuid): ?Media
    {
        if (! array_key_exists($uuid, $this->loaded)) {
            $this->loaded[$uuid] = Media::query()->with(['variants', 'tags', 'folder'])->where('uuid', $uuid)->first();
        }

        return $this->loaded[$uuid];
    }

    private function applySearch($query, ?string $search): void
    {
        $term = is_string($search) ? trim($search) : '';

        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';
        $usageUuids = $this->mediaUuidsMatchingUsage($like);

        $query->where(function ($inner) use ($like, $usageUuids): void {
            $inner->where('original_filename', 'like', $like)
                ->orWhere('filename', 'like', $like)
                ->orWhere('alt_text', 'like', $like)
                ->orWhere('caption', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('mime_type', 'like', $like)
                ->orWhere('uuid', 'like', $like)
                ->orWhere('meta->wordpress_path', 'like', $like)
                ->orWhereHas('folder', static function ($folderQuery) use ($like): void {
                    $folderQuery->where('name', 'like', $like);
                })
                ->orWhereHas('tags', static function ($tagQuery) use ($like): void {
                    $tagQuery->where(function ($match) use ($like): void {
                        $match->where('name', 'like', $like)
                            ->orWhere('slug', 'like', $like);
                    });
                });

            if ($usageUuids !== []) {
                $inner->orWhereIn('uuid', $usageUuids);
            }
        });
    }

    /**
     * @return list<string>
     */
    private function mediaUuidsMatchingUsage(string $like): array
    {
        $uuids = [];
        $sources = config('media.usage_sources', []);

        if (is_array($sources)) {
            foreach ($sources as $source) {
                if (! is_array($source)) {
                    continue;
                }

                foreach ($this->usageSourceMediaUuids($source, $like) as $uuid) {
                    $uuids[] = $uuid;
                }
            }
        }

        if (Schema::hasTable('product_media') && Schema::hasTable('product_variants')
            && Schema::hasColumn('product_media', 'media_uuid')
            && Schema::hasColumn('product_variants', 'sku')
        ) {
            foreach (DB::table('product_media')
                ->join('product_variants', 'product_variants.product_id', '=', 'product_media.product_id')
                ->where('product_variants.sku', 'like', $like)
                ->whereNotNull('product_media.media_uuid')
                ->pluck('product_media.media_uuid') as $uuid
            ) {
                if (is_string($uuid) && $uuid !== '') {
                    $uuids[] = $uuid;
                }
            }
        }

        return array_values(array_unique($uuids));
    }

    /**
     * @param  array<string, mixed>  $source
     * @return list<string>
     */
    private function usageSourceMediaUuids(array $source, string $like): array
    {
        $table = $source['table'] ?? null;
        $column = $source['column'] ?? null;

        if (! is_string($table) || ! is_string($column) || $table === '' || $column === '') {
            return [];
        }

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return [];
        }

        $ownerTable = $source['owner_table'] ?? null;
        $foreignKey = $source['foreign_key'] ?? null;
        $ownerKey = $source['owner_key'] ?? null;
        $title = $source['title'] ?? null;
        $query = DB::table($table);

        if (is_string($ownerTable) && is_string($foreignKey) && is_string($ownerKey)
            && Schema::hasTable($ownerTable)
        ) {
            $titleColumn = is_string($title) ? $title : 'name';

            if (! Schema::hasColumn($ownerTable, $titleColumn)) {
                return [];
            }

            $query->join($ownerTable, $table.'.'.$foreignKey, '=', $ownerTable.'.'.$ownerKey)
                ->where($ownerTable.'.'.$titleColumn, 'like', $like);
        } elseif (is_string($title) && Schema::hasColumn($table, $title)) {
            $query->where($table.'.'.$title, 'like', $like);
        } else {
            return [];
        }

        return array_values(array_filter(
            $query->whereNotNull($table.'.'.$column)->pluck($table.'.'.$column)->all(),
            static fn (mixed $uuid): bool => is_string($uuid) && $uuid !== '',
        ));
    }

    private function applySizeFilter($query, ?string $size): void
    {
        $filters = config('media.size_filters', []);
        $bucket = is_array($filters) ? ($filters[$size] ?? null) : null;

        if (! is_array($bucket)) {
            return;
        }

        if (isset($bucket['min'])) {
            $query->where('size', '>=', (int) $bucket['min']);
        }

        if (isset($bucket['max'])) {
            $query->where('size', '<', (int) $bucket['max']);
        }
    }

    private function applySort($query, ?string $sort, string $direction): void
    {
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'name' => $query->orderBy('original_filename', $direction),
            'type' => $query->orderBy('media_type', $direction)->orderBy('mime_type', $direction),
            'dimensions' => $query->orderBy('width', $direction),
            'size' => $query->orderBy('size', $direction),
            'folder' => $query->orderBy('folder_id', $direction),
            default => $query->orderBy('created_at', $direction),
        };
    }

    private function diskUrl(Media $media, string $path): ?string
    {
        $disk = is_string($media->disk) ? $media->disk : '';

        if ($disk !== '' && is_array(config('filesystems.disks.'.$disk))) {
            return Storage::disk($disk)->url($path);
        }

        $source = is_array($media->meta) ? ($media->meta['source_url'] ?? null) : null;

        if (! is_string($source) || $source === '') {
            return null;
        }

        if ($path === $media->path) {
            return $source;
        }

        return rtrim(dirname($source), '/').'/'.ltrim($path, '/');
    }

    private function findVariant(Media $media, string $name): ?MediaVariant
    {
        foreach ($this->variantCandidates($name) as $candidate) {
            $match = $media->variants->firstWhere('name', $candidate);

            if ($match instanceof MediaVariant) {
                return $match;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function variantCandidates(string $name): array
    {
        $aliases = config('media.aliases', []);
        $canonical = is_array($aliases) ? ($aliases[$name] ?? $name) : $name;
        $legacy = is_array($aliases) ? array_search($canonical, $aliases, true) : false;

        return array_values(array_unique(array_filter([
            $canonical,
            $name,
            is_string($legacy) ? $legacy : null,
        ])));
    }
}
