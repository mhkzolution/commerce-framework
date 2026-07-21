<?php

declare(strict_types=1);

namespace Commerce\Tax\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpsertTaxRateData extends DataTransferObject
{
    public function __construct(
        public string $code,
        public string $name,
        public int $rateBps,
        public ?string $countryCode = null,
        public bool $isActive = true,
        public int $priority = 0,
    ) {}
}
