<?php

declare(strict_types=1);

namespace Commerce\Contracts\Inventory;

interface InventoryQueryServiceInterface
{
    public function findByPurchasableUuid(string $purchasableUuid): ?StockLevelInterface;

    public function getStockLevel(string $purchasableUuid): StockLevelInterface;

    public function isAvailable(string $purchasableUuid, int $quantity = 1): bool;

    public function getAvailable(string $purchasableUuid): int;

    /**
     * @param  list<string>  $purchasableUuids
     * @return array<string, StockLevelInterface>
     */
    public function levelsForPurchasables(array $purchasableUuids): array;
}
