<?php

declare(strict_types=1);

namespace Commerce\Inventory\Contracts;

use Commerce\Contracts\Inventory\StockLevelInterface;

interface InventoryServiceInterface
{
    public function adjust(string $purchasableUuid, int $quantity, ?string $reason = null): StockLevelInterface;

    public function receive(string $purchasableUuid, int $quantity, ?string $reason = null): StockLevelInterface;

    public function setOnHand(string $purchasableUuid, int $onHand, ?string $reason = null): StockLevelInterface;

    public function sale(
        string $purchasableUuid,
        int $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $reason = null,
    ): StockLevelInterface;

    public function returnStock(
        string $purchasableUuid,
        int $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $reason = null,
    ): StockLevelInterface;

    public function reserve(
        string $purchasableUuid,
        int $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $reason = null,
    ): StockLevelInterface;

    public function release(
        string $purchasableUuid,
        int $quantity,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $reason = null,
    ): StockLevelInterface;
}
