<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class HomepageBrandingData extends DataTransferObject
{
    public function __construct(
        public string $name,
        public ?string $logoUrl = null,
    ) {}
}
