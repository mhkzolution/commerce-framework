<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CartData extends DataTransferObject
{
    /**
     * @param  list<ResolvedCartLineData>  $lines
     */
    public function __construct(
        public string $currency,
        public array $lines,
        public int $subtotal,
        public int $itemCount,
        public int $discountTotal = 0,
        public ?string $couponCode = null,
        public ?string $promotionName = null,
    ) {}

    public function taxableSubtotal(): int
    {
        return max(0, $this->subtotal - $this->discountTotal);
    }
}
