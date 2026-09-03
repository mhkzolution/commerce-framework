<?php

declare(strict_types=1);

namespace Commerce\Contracts\Settings;

final readonly class WebsiteSocialLinkData
{
    public function __construct(
        public string $key,
        public string $label,
        public string $url,
    ) {}
}
