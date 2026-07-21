<?php

declare(strict_types=1);

namespace Commerce\Shipping\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpdateShippingMethodData extends DataTransferObject
{
    /**
     * @param  list<string>|null  $countries
     */
    public function __construct(
        public string $code,
        public string $name,
        public int $price,
        public ?string $description = null,
        public ?int $freeAbove = null,
        public ?int $minSubtotal = null,
        public ?int $maxSubtotal = null,
        public ?array $countries = null,
        public bool $isActive = true,
        public int $sortOrder = 0,
    ) {}
}
