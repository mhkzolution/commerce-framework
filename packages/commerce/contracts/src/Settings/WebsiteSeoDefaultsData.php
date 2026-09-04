<?php

declare(strict_types=1);

namespace Commerce\Contracts\Settings;

final readonly class WebsiteSeoDefaultsData
{
    public function __construct(
        public ?string $titleSuffix,
        public ?string $defaultDescription,
        public ?string $defaultOgImageUrl,
    ) {}
}
