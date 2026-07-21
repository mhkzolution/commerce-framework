<?php

declare(strict_types=1);

namespace Commerce\Media\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpdateMediaData extends DataTransferObject
{
    public function __construct(
        public ?string $altText = null,
        public ?int $folderId = null,
        public bool $syncFolder = false,
    ) {}
}
