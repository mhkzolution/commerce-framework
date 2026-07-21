<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Contracts\Search\SearchQueryInterface;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

final class ProductQueryService extends BaseQueryService implements ProductQueryServiceInterface
{
    public function __construct(
        private readonly SearchQueryInterface $searchQuery,
    ) {}

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

    public function paginateStorefrontSearch(string $search, int $perPage = 25)
    {
        if (trim($search) === '') {
            return $this->paginateStorefront($perPage);
        }

        $result = $this->searchQuery->search(self::storefrontSearchIndex(), $search, ['status' => 'published'], 1, $perPage);
        $uuids = array_values(array_filter(array_map(
            static fn (array $hit): ?string => isset($hit['uuid']) ? (string) $hit['uuid'] : null,
            $result->getHits(),
        )));

        if ($uuids === []) {
            return Product::query()->whereRaw('1 = 0')->paginate($perPage);
        }

        $products = Product::query()
            ->with(['variants', 'media', 'categories', 'tags', 'attributeValues.attribute'])
            ->visibleOnStorefront()
            ->whereIn('uuid', $uuids)
            ->get()
            ->keyBy('uuid');

        $ordered = collect($uuids)
            ->map(static fn (string $uuid) => $products->get($uuid))
            ->filter()
            ->values()
            ->all();

        return new Paginator($ordered, $result->getTotal(), $perPage, $result->getPage());
    }

    public function findStorefrontBySlug(string $slug): ?Product
    {
        return Product::query()
            ->with(['variants', 'media', 'categories', 'tags', 'attributeValues.attribute'])
            ->visibleOnStorefront()
            ->where('slug', $slug)
            ->first();
    }

    private static function storefrontSearchIndex(): string
    {
        return ProductSearchIndexer::INDEX;
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(?string $search = null, ?string $status = null, int $perPage = 25)
    {
        if ($search !== null && $search !== '') {
            return $this->paginateViaSearch($search, $status, $perPage);
        }

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

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    private function paginateViaSearch(string $search, ?string $status, int $perPage): LengthAwarePaginator
    {
        $filters = [];
        if ($status !== null && $status !== '') {
            $filters['status'] = $status === 'published' ? 'published' : $status;
        }

        $result = $this->searchQuery->search(ProductSearchIndexer::INDEX, $search, $filters, 1, $perPage);
        $uuids = array_values(array_filter(array_map(
            static fn (array $hit): ?string => isset($hit['uuid']) ? (string) $hit['uuid'] : null,
            $result->getHits(),
        )));

        if ($uuids === []) {
            return new Paginator([], 0, $perPage);
        }

        $products = Product::query()
            ->with(['variants', 'media', 'categories'])
            ->whereIn('uuid', $uuids)
            ->get()
            ->keyBy('uuid');

        $ordered = collect($uuids)
            ->map(static fn (string $uuid) => $products->get($uuid))
            ->filter()
            ->values()
            ->all();

        return new Paginator($ordered, $result->getTotal(), $perPage, $result->getPage());
    }
}
