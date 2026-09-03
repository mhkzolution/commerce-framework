<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\DTO\HomepageNavigationData;
use Commerce\Catalog\Models\Category;
use Illuminate\Support\Collection;
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
        return $this->mapCategories(
            $this->activeCategories()
                ->filter(static fn (Category $category): bool => $category->parent_id === null && filled($category->slug))
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
}
