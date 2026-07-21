<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CartLineData extends DataTransferObject
{
    public function __construct(
        public string $purchasableUuid,
        public int $quantity,
    ) {}
}
