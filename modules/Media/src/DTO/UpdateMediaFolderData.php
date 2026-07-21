<?php

declare(strict_types=1);

namespace Commerce\Media\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpdateMediaFolderData extends DataTransferObject
{
    public function __construct(
        public string $name,
        public ?string $parentUuid = null,
    ) {}
}
