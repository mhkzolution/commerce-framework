<?php

declare(strict_types=1);

namespace Commerce\Catalog\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpdateBrandData extends DataTransferObject
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public ?string $description = null,
        public ?string $logoMediaUuid = null,
        public bool $isActive = true,
    ) {}
}
