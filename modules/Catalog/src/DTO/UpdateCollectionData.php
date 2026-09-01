<?php

declare(strict_types=1);

namespace Commerce\Catalog\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpdateCollectionData extends DataTransferObject
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public ?string $description = null,
        public ?string $coverMediaUuid = null,
        public ?string $type = null,
        /** @var array<string, mixed>|null */
        public ?array $rules = null,
        /** @var array<string, mixed>|null */
        public ?array $seo = null,
    ) {}
}
