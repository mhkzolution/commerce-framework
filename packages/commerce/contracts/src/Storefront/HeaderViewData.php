<?php

declare(strict_types=1);

namespace Commerce\Contracts\Storefront;

final readonly class HeaderViewData
{
    public function __construct(
        public HeaderBrandData $brand,
        public HeaderNavigationData $navigation,
        public HeaderActionData $actions,
    ) {}
}
