<?php

declare(strict_types=1);

namespace Commerce\Media\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpdateMediaData extends DataTransferObject
{
    /**
     * @param  list<string>|null  $tags
     * @param  array<string, mixed>|null  $crop
     */
    public function __construct(
        public ?string $altText = null,
        public ?string $caption = null,
        public ?string $description = null,
        public ?int $folderId = null,
        public bool $syncFolder = false,
        public ?array $tags = null,
        public ?array $crop = null,
    ) {}
}
