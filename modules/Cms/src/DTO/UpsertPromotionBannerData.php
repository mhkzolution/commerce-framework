<?php

declare(strict_types=1);

namespace Commerce\Cms\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpsertPromotionBannerData extends DataTransferObject
{
    public function __construct(
        public string $title,
        public string $imageMediaUuid,
        public ?string $url = null,
        public bool $openInNewTab = false,
        public int $sortOrder = 0,
        public bool $isActive = true,
        public ?string $startsAt = null,
        public ?string $endsAt = null,
    ) {}
}
