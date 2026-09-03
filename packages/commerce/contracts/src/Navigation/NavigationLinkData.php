<?php

declare(strict_types=1);

namespace Commerce\Contracts\Navigation;

final readonly class NavigationLinkData
{
    public function __construct(
        public string $label,
        public string $url,
        public ?string $key = null,
        public bool $footerEnabled = true,
    ) {}
}
