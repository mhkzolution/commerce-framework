<?php

declare(strict_types=1);

namespace Commerce\Cms\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CreateCategoryData extends DataTransferObject
{
    /** @param  array<string, mixed>|null  $seo */
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public ?string $description = null,
        public ?string $imageMediaUuid = null,
        public ?int $parentId = null,
        public bool $isActive = true,
        public int $position = 0,
        public ?array $seo = null,
    ) {}
}
