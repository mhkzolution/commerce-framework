<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\DTO\HomepageNavigationData;
use Commerce\Catalog\Models\Category;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class HomepageNavigationQuery
{
    /**
     * @return list<HomepageNavigationData>
     */
    public function arrivalTabs(): array
    {
        return array_slice($this->visibleTopLevel(), 0, 8);
    }

    /**
     * @return list<HomepageNavigationData>
     */
    public function shopFilterOptions(): array
    {
        return $this->visibleTopLevel();
    }

    /**
     * @return list<HomepageNavigationData>
     */
    public function featured(): array
    {
        return array_slice($this->visibleTopLevel(), 0, 8);
    }

    /**
     * @return list<HomepageNavigationData>
     */
    public function sections(): array
    {
        return $this->featured();
    }

    /**
     * @return Collection<int, Category>
     */
    private function activeCategories(): Collection
    {
        try {
            if (! class_exists(Category::class) || ! Schema::hasTable('categories')) {
                return collect();
            }

            return Category::query()
                ->where('is_active', true)
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * @return list<HomepageNavigationData>
     */
    private function visibleTopLevel(): array
    {
        $categories = $this->activeCategories()
            ->filter(static fn (Category $category): bool => filled($category->slug))
            ->values();

        if ($categories->isEmpty()) {
            return [];
        }

        $inclusive = $this->inclusiveCounts($categories);
        $byParent = $categories->groupBy(static fn (Category $category): int => (int) ($category->parent_id ?? 0));

        $roots = $categories
            ->filter(static fn (Category $category): bool => $category->parent_id === null)
            ->sortBy([
                ['position', 'asc'],
                ['name', 'asc'],
            ])
            ->values();

        $nodes = [];

        foreach ($roots as $root) {
            $node = $this->toNode($root, $byParent, $inclusive);
            if ($node !== null) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * @param  Collection<int, Collection<int, Category>>  $byParent
     * @param  array<int, int>  $inclusive
     */
    private function toNode(Category $category, Collection $byParent, array $inclusive): ?HomepageNavigationData
    {
        $id = (int) $category->id;
        $count = $inclusive[$id] ?? 0;

        if ($count < 1) {
            return null;
        }

        $children = [];
        foreach (($byParent[$id] ?? collect())->sortBy([['position', 'asc'], ['name', 'asc']])->values() as $child) {
            $childNode = $this->toNode($child, $byParent, $inclusive);
            if ($childNode !== null) {
                $children[] = $childNode;
            }
        }

        $slug = trim((string) $category->slug);

        return new HomepageNavigationData(
            uuid: (string) $category->uuid,
            name: (string) $category->name,
            slug: $slug,
            url: $this->categoryUrl($slug),
            imageUrl: $this->categoryImageUrl($category),
            productCount: $count,
            imageSrcset: $this->categoryImageSrcset($category),
            children: $children,
        );
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return array<int, int>
     */
    private function inclusiveCounts(Collection $categories): array
    {
        $direct = $this->productCounts($categories);
        $childrenOf = [];

        foreach ($categories as $category) {
            $parentId = (int) ($category->parent_id ?? 0);
            $childrenOf[$parentId][] = (int) $category->id;
        }

        $memo = [];

        $walk = function (int $id) use (&$walk, &$memo, $direct, $childrenOf): int {
            if (array_key_exists($id, $memo)) {
                return $memo[$id];
            }

            $total = $direct[$id] ?? 0;

            foreach ($childrenOf[$id] ?? [] as $childId) {
                $total += $walk($childId);
            }

            return $memo[$id] = $total;
        };

        $inclusive = [];
        foreach ($categories as $category) {
            $inclusive[(int) $category->id] = $walk((int) $category->id);
        }

        return $inclusive;
    }

    private function categoryUrl(string $slug): ?string
    {
        if (Route::has('storefront.shop.index')) {
            return route('storefront.shop.index', ['category' => $slug]);
        }

        return null;
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return array<int, int>
     */
    private function productCounts(Collection $categories): array
    {
        $ids = $categories->pluck('id')->filter()->map(static fn (mixed $id): int => (int) $id)->all();
        if ($ids === [] || ! Schema::hasTable('product_categories')) {
            return [];
        }

        try {
            return DB::table('product_categories')
                ->whereIn('category_id', $ids)
                ->selectRaw('category_id, COUNT(*) as aggregate')
                ->groupBy('category_id')
                ->pluck('aggregate', 'category_id')
                ->map(static fn (mixed $count): int => (int) $count)
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function categoryImageUuid(Category $category): ?string
    {
        $uuid = $category->image_media_uuid ?? data_get($category->meta, 'image_media_uuid');

        return is_string($uuid) && $uuid !== '' ? $uuid : null;
    }

    private function categoryImageUrl(Category $category): ?string
    {
        $uuid = $this->categoryImageUuid($category);
        if ($uuid === null || ! app()->bound(MediaQueryServiceInterface::class)) {
            return null;
        }

        try {
            $media = app(MediaQueryServiceInterface::class);

            return $media->getUrl($uuid, 'card')
                ?? $media->getUrl($uuid, 'medium')
                ?? $media->getUrl($uuid, 'thumbnail')
                ?? $media->getUrl($uuid);
        } catch (Throwable) {
            return null;
        }
    }

    private function categoryImageSrcset(Category $category): ?string
    {
        $uuid = $this->categoryImageUuid($category);
        if ($uuid === null || ! app()->bound(MediaQueryServiceInterface::class)) {
            return null;
        }

        try {
            return app(MediaQueryServiceInterface::class)->getSrcset($uuid);
        } catch (Throwable) {
            return null;
        }
    }
}
