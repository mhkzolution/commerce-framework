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
        return $this->mapCategories(
            $this->activeCategories()
                ->filter(static fn (Category $category): bool => filled($category->slug))
                ->sortBy([
                    ['position', 'asc'],
                    ['name', 'asc'],
                ])
                ->take(8)
                ->values(),
        );
    }

    /**
     * @return list<HomepageNavigationData>
     */
    public function shopFilterOptions(): array
    {
        return $this->mapCategories(
            $this->activeCategories()
                ->filter(static fn (Category $category): bool => filled($category->slug))
                ->sortBy([
                    ['position', 'asc'],
                    ['name', 'asc'],
                ])
                ->values(),
        );
    }

    /**
     * @return list<HomepageNavigationData>
     */
    public function featured(): array
    {
        $categories = $this->activeCategories()
            ->filter(static fn (Category $category): bool => $category->parent_id === null && filled($category->slug))
            ->sortBy([
                ['position', 'asc'],
                ['name', 'asc'],
            ])
            ->take(8)
            ->values();

        $counts = $this->productCounts($categories);

        return $categories
            ->map(function (Category $category) use ($counts): HomepageNavigationData {
                $slug = trim((string) $category->slug);

                return new HomepageNavigationData(
                    uuid: (string) $category->uuid,
                    name: (string) $category->name,
                    slug: $slug,
                    url: $this->categoryUrl($slug),
                    imageUrl: $this->categoryImageUrl($category),
                    productCount: $counts[$category->id] ?? 0,
                );
            })
            ->all();
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
     * @param  Collection<int, Category>  $categories
     * @return list<HomepageNavigationData>
     */
    private function mapCategories(Collection $categories): array
    {
        return $categories
            ->map(function (Category $category): HomepageNavigationData {
                $slug = trim((string) $category->slug);

                return new HomepageNavigationData(
                    uuid: (string) $category->uuid,
                    name: (string) $category->name,
                    slug: $slug,
                    url: $this->categoryUrl($slug),
                );
            })
            ->all();
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

    private function categoryImageUrl(Category $category): ?string
    {
        $uuid = data_get($category->meta, 'image_media_uuid');
        if (! is_string($uuid) || $uuid === '' || ! app()->bound(MediaQueryServiceInterface::class)) {
            return null;
        }

        try {
            $media = app(MediaQueryServiceInterface::class);

            return $media->getUrl($uuid, 'medium')
                ?? $media->getUrl($uuid, 'thumbnail')
                ?? $media->getUrl($uuid);
        } catch (Throwable) {
            return null;
        }
    }
}
