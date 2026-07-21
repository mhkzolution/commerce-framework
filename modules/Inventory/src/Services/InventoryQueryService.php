<?php

declare(strict_types=1);

namespace Commerce\Inventory\Services;

use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Inventory\StockLevelInterface;
use Commerce\Contracts\Inventory\StockMovementType;
use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Inventory\DTO\StockLevel;
use Commerce\Inventory\Models\InventoryItem;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class InventoryQueryService extends BaseQueryService implements InventoryQueryServiceInterface
{
    public function findByPurchasableUuid(string $purchasableUuid): ?StockLevelInterface
    {
        $item = InventoryItem::query()
            ->where('purchasable_uuid', $purchasableUuid)
            ->first();

        if ($item === null) {
            return null;
        }

        return $this->toStockLevel($item);
    }

    public function getStockLevel(string $purchasableUuid): StockLevelInterface
    {
        return $this->findByPurchasableUuid($purchasableUuid)
            ?? new StockLevel($purchasableUuid, 0, 0);
    }

    public function isAvailable(string $purchasableUuid, int $quantity = 1): bool
    {
        return $this->getAvailable($purchasableUuid) >= $quantity;
    }

    public function getAvailable(string $purchasableUuid): int
    {
        return $this->getStockLevel($purchasableUuid)->getAvailable();
    }

    /**
     * @return LengthAwarePaginator<int, InventoryItem>
     */
    public function paginate(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return InventoryItem::query()
            ->when($search, static function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('purchasable_uuid', 'like', "%{$search}%")
                        ->orWhereExists(static function ($exists) use ($search): void {
                            $exists->select(DB::raw(1))
                                ->from('product_variants')
                                ->whereColumn('product_variants.uuid', 'inventory_items.purchasable_uuid')
                                ->where(function ($variantQuery) use ($search): void {
                                    $variantQuery->where('product_variants.sku', 'like', "%{$search}%")
                                        ->orWhere('product_variants.name', 'like', "%{$search}%")
                                        ->orWhereExists(static function ($productExists) use ($search): void {
                                            $productExists->select(DB::raw(1))
                                                ->from('products')
                                                ->whereColumn('products.id', 'product_variants.product_id')
                                                ->where('products.name', 'like', "%{$search}%");
                                        });
                                });
                        });
                });
            })
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function findItemByUuid(string $uuid): ?InventoryItem
    {
        return InventoryItem::query()
            ->with('movements')
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @return array<string, StockLevelInterface>
     */
    public function levelsForPurchasables(array $purchasableUuids): array
    {
        if ($purchasableUuids === []) {
            return [];
        }

        $items = InventoryItem::query()
            ->whereIn('purchasable_uuid', $purchasableUuids)
            ->get()
            ->keyBy('purchasable_uuid');

        $levels = [];
        foreach ($purchasableUuids as $uuid) {
            $item = $items->get($uuid);
            $levels[$uuid] = $item !== null
                ? $this->toStockLevel($item)
                : new StockLevel($uuid, 0, 0);
        }

        return $levels;
    }

    /**
     * @return array<string, array{variant: ?ProductVariant, product_name: ?string}>
     */
    public function variantContextForItems(iterable $items): array
    {
        $uuids = [];
        foreach ($items as $item) {
            $uuids[] = $item->purchasable_uuid;
        }

        if ($uuids === []) {
            return [];
        }

        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('uuid', $uuids)
            ->get()
            ->keyBy('uuid');

        $context = [];
        foreach ($uuids as $uuid) {
            $variant = $variants->get($uuid);
            $context[$uuid] = [
                'variant' => $variant,
                'product_name' => $variant?->product?->name,
            ];
        }

        return $context;
    }

    private function toStockLevel(InventoryItem $item): StockLevel
    {
        return new StockLevel(
            purchasableUuid: $item->purchasable_uuid,
            onHand: $item->on_hand,
            reserved: $item->reserved,
        );
    }
}
