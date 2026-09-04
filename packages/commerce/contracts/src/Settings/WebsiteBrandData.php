<?php

declare(strict_types=1);

namespace Commerce\Contracts\Settings;

final readonly class WebsiteBrandData
{
    public function __construct(
        public string $name,
        public ?string $logoUrl,
        public ?string $description,
    ) {}
}
