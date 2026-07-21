<?php

declare(strict_types=1);

namespace Commerce\Contracts\Inventory;

interface StockLevelInterface
{
    public function getPurchasableUuid(): string;

    public function getOnHand(): int;

    public function getReserved(): int;

    public function getAvailable(): int;
}
