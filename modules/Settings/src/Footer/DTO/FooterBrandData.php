<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class FooterBrandData extends DataTransferObject
{
    public function __construct(
        public ?string $displayName = null,
        public ?string $logoUrl = null,
        public ?string $description = null,
    ) {}
}
