<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class StorefrontInStockCatalog
{
    /** @var list<int>|null */
    private ?array $productIds = null;

    /**
     * @return list<int>
     */
    public function productIds(): array
    {
        if ($this->productIds !== null) {
            return $this->productIds;
        }

        $inStockUuids = DB::table('inventory_items')
            ->whereRaw('(on_hand - reserved) > 0')
            ->pluck('purchasable_uuid')
            ->all();

        if ($inStockUuids === []) {
            return $this->productIds = [];
        }

        $this->productIds = DB::table('product_variants')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->whereIn('product_variants.uuid', $inStockUuids)
            ->where('products.status', 'published')
            ->where('products.visibility', 'public')
            ->whereNull('product_variants.deleted_at')
            ->whereNull('products.deleted_at')
            ->distinct()
            ->pluck('products.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        return $this->productIds;
    }

    /**
     * @param  Builder<Model>  $query
     */
    public function applyInStockVariantConstraint(Builder $query): void
    {
        $productIds = $this->productIds();

        if ($productIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn($query->getModel()->getQualifiedKeyName(), $productIds);
    }
}
