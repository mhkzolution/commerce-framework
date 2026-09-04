<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class StorefrontNavigationCatalog
{
    /**
     * @return SupportCollection<int, Category>
     */
    public function categories(): SupportCollection
    {
        try {
            if (! class_exists(Category::class) || ! Schema::hasTable('categories')) {
                return collect();
            }

            return Category::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->orderBy('name')
                ->get()
                ->filter(static fn (Category $category): bool => filled($category->slug))
                ->values();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * @return SupportCollection<int, mixed>
     */
    public function collections(): SupportCollection
    {
        return collect();
    }

    /**
     * @return SupportCollection<int, Brand>
     */
    public function brands(): SupportCollection
    {
        try {
            if (! class_exists(Brand::class) || ! Schema::hasTable('brands')) {
                return collect();
            }

            return Brand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->filter(static fn (Brand $brand): bool => filled($brand->slug))
                ->values();
        } catch (Throwable) {
            return collect();
        }
    }
}
