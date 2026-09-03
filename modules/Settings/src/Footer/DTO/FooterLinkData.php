<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class FooterLinkData extends DataTransferObject
{
    public function __construct(
        public string $label,
        public string $url,
        public ?string $key = null,
    ) {}
}
