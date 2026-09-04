<?php

declare(strict_types=1);

namespace Commerce\Orders\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class OrderLineData extends DataTransferObject
{
    public function __construct(
        public string $purchasableUuid,
        public int $quantity,
        public ?int $unitPrice = null,
    ) {}
}
