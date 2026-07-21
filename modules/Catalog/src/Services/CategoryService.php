<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Contracts\CategoryServiceInterface;
use Commerce\Catalog\DTO\CreateCategoryData;
use Commerce\Catalog\DTO\UpdateCategoryData;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Support\SlugGenerator;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\EntityNotFoundException;

final class CategoryService extends BaseService implements CategoryServiceInterface
{
    public function create(CreateCategoryData $data): Category
    {
        $slug = $data->slug ?? SlugGenerator::unique($data->name, Category::query());

        return Category::query()->create([
            'name' => $data->name,
            'slug' => $slug,
            'description' => $data->description,
            'parent_id' => $data->parentId,
            'is_active' => $data->isActive,
            'position' => $data->position,
        ]);
    }

    public function update(string $uuid, UpdateCategoryData $data): Category
    {
        $category = $this->findOrFail($uuid);

        $slug = $data->slug ?? SlugGenerator::unique($data->name, Category::query(), $category->id);

        $category->update([
            'name' => $data->name,
            'slug' => $slug,
            'description' => $data->description,
            'parent_id' => $data->parentId,
            'is_active' => $data->isActive,
            'position' => $data->position,
        ]);

        return $category->fresh();
    }

    public function delete(string $uuid): void
    {
        $category = $this->findOrFail($uuid);
        $category->delete();
    }

    public function reorder(string $uuid, int $position): Category
    {
        $category = $this->findOrFail($uuid);
        $category->update(['position' => $position]);

        return $category->fresh();
    }

    private function findOrFail(string $uuid): Category
    {
        $category = Category::query()->where('uuid', $uuid)->first();

        if ($category === null) {
            throw new EntityNotFoundException("Category [{$uuid}] not found.");
        }

        return $category;
    }
}
