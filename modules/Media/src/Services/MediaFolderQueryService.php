<?php

declare(strict_types=1);

namespace Commerce\Media\Services;

use Commerce\Core\Base\BaseQueryService;
use Commerce\Media\Models\MediaFolder;

final class MediaFolderQueryService extends BaseQueryService
{
    public function findByUuid(string $uuid): ?MediaFolder
    {
        return MediaFolder::query()->where('uuid', $uuid)->first();
    }

    /**
     * @return list<MediaFolder>
     */
    public function tree(?int $parentId = null): array
    {
        return MediaFolder::query()
            ->with($this->childrenRecursive())
            ->withCount('media')
            ->where('parent_id', $parentId)
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function childrenRecursive(): array
    {
        return [
            'children' => function ($query): void {
                $query->withCount('media')->orderBy('name')->with($this->childrenRecursive());
            },
        ];
    }

    /**
     * @return list<MediaFolder>
     */
    public function flat(): array
    {
        return MediaFolder::query()->with('parent')->orderBy('name')->get()->all();
    }
}
