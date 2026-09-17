<?php

declare(strict_types=1);

namespace Commerce\Cms\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpsertPopupData extends DataTransferObject
{
    public function __construct(
        public string $title,
        public string $slug,
        public string $status = 'draft',
        public int $priority = 0,
        public bool $isActive = true,
        public ?string $headline = null,
        public ?string $subheadline = null,
        public ?string $imageMediaUuid = null,
        public ?string $buttonText = null,
        public ?string $buttonUrl = null,
        public string $buttonTarget = 'self',
        public string $popupType = 'image',
        public int $showDelay = 0,
        public ?int $autoClose = null,
        public bool $closable = true,
        public ?string $startAt = null,
        public ?string $endAt = null,
        public string $timezone = 'Asia/Bangkok',
    ) {}
}
