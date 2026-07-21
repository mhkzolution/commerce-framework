<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Models\Category;
use Commerce\Contracts\Catalog\CategoryQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;

final class CategoryQueryService extends BaseQueryService implements CategoryQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object
    {
        return Category::query()->where('uuid', $uuid)->first();
    }

    public function findBySlug(string $slug): ?object
    {
        return Category::query()->where('slug', $slug)->first();
    }

    public function tree(?int $parentId = null): array
    {
        return Category::query()
            ->with($this->childrenRecursive())
            ->where('parent_id', $parentId)
            ->orderBy('position')
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
                $query->orderBy('position')->orderBy('name')->with($this->childrenRecursive());
            },
        ];
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Category>
     */
    public function paginate(int $perPage = 25)
    {
        return Category::query()
            ->with('parent')
            ->orderBy('position')
            ->orderBy('name')
            ->paginate($perPage);
    }
}
