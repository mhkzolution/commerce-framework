<?php

declare(strict_types=1);

namespace Commerce\Promotion\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class UpsertPromotionData extends DataTransferObject
{
    public function __construct(
        public string $code,
        public string $name,
        public string $type,
        public int $value,
        public ?int $minSubtotal = null,
        public ?int $maxUses = null,
        public ?\DateTimeInterface $startsAt = null,
        public ?\DateTimeInterface $endsAt = null,
        public bool $isActive = true,
    ) {}
}
