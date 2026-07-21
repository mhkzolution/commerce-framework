<?php

declare(strict_types=1);

namespace Commerce\Currency\DTO;

final readonly class UpdateCurrencyData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $symbol,
        public int $decimalPlaces,
        public int $rateMicro,
        public bool $isBase,
        public bool $isActive,
        public int $sortOrder,
    ) {}
}
