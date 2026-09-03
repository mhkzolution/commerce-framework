<?php

declare(strict_types=1);

namespace Commerce\Cms\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpsertFaqEntryData extends DataTransferObject
{
    public function __construct(
        public string $question,
        public string $answer,
        public int $sortOrder = 0,
        public bool $isActive = true,
    ) {}
}
