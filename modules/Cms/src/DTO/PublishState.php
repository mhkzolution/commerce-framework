<?php

declare(strict_types=1);

namespace Commerce\Cms\DTO;

use Carbon\CarbonInterface;

final readonly class PublishState
{
    public function __construct(
        public string $status,
        public ?CarbonInterface $publishedAt,
        public ?CarbonInterface $unpublishAt,
    ) {}
}
