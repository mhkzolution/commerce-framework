<?php

declare(strict_types=1);

namespace Commerce\Inventory\DTO;

use Commerce\Contracts\Inventory\StockLevelInterface;
use Commerce\Support\DTO\DataTransferObject;

final readonly class StockLevel extends DataTransferObject implements StockLevelInterface
{
    public function __construct(
        public string $purchasableUuid,
        public int $onHand,
        public int $reserved,
    ) {}

    public function getPurchasableUuid(): string
    {
        return $this->purchasableUuid;
    }

    public function getOnHand(): int
    {
        return $this->onHand;
    }

    public function getReserved(): int
    {
        return $this->reserved;
    }

    public function getAvailable(): int
    {
        return max(0, $this->onHand - $this->reserved);
    }
}
