<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;

final class ProductQueryService extends BaseQueryService implements ProductQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object
    {
        return Product::query()
            ->with(['variants', 'media', 'categories', 'tags', 'attributeValues.attribute'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function findBySlug(string $slug): ?object
    {
        return Product::query()
            ->with(['variants', 'media', 'categories', 'tags', 'attributeValues.attribute'])
            ->where('slug', $slug)
            ->first();
    }

    public function findVariantByUuid(string $uuid): ?object
    {
        return ProductVariant::query()
            ->with('product')
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Product>
     */
    public function paginateStorefront(int $perPage = 25)
    {
        return Product::query()
            ->with(['variants', 'media', 'categories', 'tags', 'attributeValues.attribute'])
            ->visibleOnStorefront()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Product>
     */
    public function paginate(?string $search = null, ?string $status = null, int $perPage = 25)
    {
        return Product::query()
            ->with(['variants', 'media', 'categories'])
            ->when($status === 'published', static fn ($query) => $query->published())
            ->when($status && $status !== 'published', static fn ($query) => $query->where('status', $status))
            ->when($search, static function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('variants', static fn ($variantQuery) => $variantQuery->where('sku', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($perPage);
    }
}
