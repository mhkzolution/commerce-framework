<?php

declare(strict_types=1);

namespace Commerce\WarehouseScanner\Services;

use Commerce\Barcode\DTO\BarcodeSearchResult;
use Commerce\Barcode\Services\BarcodeProductSearchService;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Inventory\Models\InventoryItem;
use Commerce\Inventory\Models\InventoryLocation;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Support\Facades\Route;

final class ScannerProductLookupService
{
    public function __construct(
        private readonly BarcodeProductSearchService $productSearch,
        private readonly InventoryQueryServiceInterface $inventoryQuery,
    ) {}

    /**
     * @return array{
     *     variant_uuid: string,
     *     thumbnail_url: string|null,
     *     product_name: string,
     *     variant_name: string,
     *     owner_name: string,
     *     sku: string,
     *     on_hand: int,
     *     available: int,
     *     reserved: int,
     *     location: array{code: string, name: string}|null,
     *     shelf: string|null,
     *     status: string,
     *     product_url: string|null
     * }|null
     */
    public function lookupBySku(string $sku): ?array
    {
        $sku = trim($sku);

        if ($sku === '') {
            return null;
        }

        $match = $this->productSearch->findBySku($sku);

        if (! $match instanceof BarcodeSearchResult) {
            return null;
        }

        $base = $match->toArray();
        $stock = $this->inventoryQuery->getStockLevel($base['variant_uuid']);
        $item = InventoryItem::query()
            ->where('purchasable_uuid', $base['variant_uuid'])
            ->first();

        $variant = ProductVariant::query()
            ->with('product')
            ->where('uuid', $base['variant_uuid'])
            ->first();

        $location = InventoryLocation::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        $shelf = $this->resolveShelf($item, $variant);
        $available = $stock->getAvailable();
        $threshold = (int) config('warehouse-scanner.low_stock_threshold', 5);

        return [
            ...$base,
            'on_hand' => $stock->getOnHand(),
            'available' => $available,
            'reserved' => $stock->getReserved(),
            'location' => $location ? [
                'code' => (string) $location->code,
                'name' => (string) $location->name,
            ] : null,
            'shelf' => $shelf,
            'status' => $this->resolveStatus($available, $threshold, $variant?->status),
            'product_url' => $this->productAdminUrl($variant),
        ];
    }

    private function resolveShelf(?InventoryItem $item, ?ProductVariant $variant): ?string
    {
        $fromItem = $item?->meta['shelf'] ?? null;
        if (is_string($fromItem) && $fromItem !== '') {
            return $fromItem;
        }

        $fromVariant = $variant?->meta['warehouse_shelf'] ?? null;

        return is_string($fromVariant) && $fromVariant !== '' ? $fromVariant : null;
    }

    private function resolveStatus(int $available, int $threshold, ?string $variantStatus): string
    {
        if ($variantStatus === 'archived') {
            return 'archived';
        }

        if ($available <= 0) {
            return 'out';
        }

        if ($available <= $threshold) {
            return 'low';
        }

        return 'in_stock';
    }

    private function productAdminUrl(?ProductVariant $variant): ?string
    {
        if ($variant?->product === null && $variant !== null) {
            $variant->loadMissing('product');
        }

        $product = $variant?->product;

        if ($product === null) {
            return null;
        }

        if (! Route::has('admin.products.edit')) {
            return null;
        }

        return route('admin.products.edit', $product);
    }
}
