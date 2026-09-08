<?php

declare(strict_types=1);

namespace Commerce\Product\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class SuggestHit extends DataTransferObject
{
    public function __construct(
        public string $label,
        public string $url,
    ) {}
}
