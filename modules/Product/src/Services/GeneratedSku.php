<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

final readonly class GeneratedSku
{
    public function __construct(
        public string $sku,
        public bool $isAuto,
    ) {}
}
