<?php

declare(strict_types=1);

namespace Commerce\Cms\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpsertHeroBannerData extends DataTransferObject
{
    public function __construct(
        public string $imageMediaUuid,
        public ?string $mobileImageMediaUuid = null,
        public string $type = 'image',
        public ?string $videoMediaUuid = null,
        public ?string $mobileVideoMediaUuid = null,
        public int $sortOrder = 0,
        public bool $isActive = true,
        public ?string $startsAt = null,
        public ?string $endsAt = null,
    ) {}
}
