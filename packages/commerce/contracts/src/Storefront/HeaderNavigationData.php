<?php

declare(strict_types=1);

namespace Commerce\Contracts\Storefront;

use Commerce\Contracts\Navigation\NavigationLinkData;

final readonly class HeaderNavigationData
{
    /**
     * @param  list<NavigationLinkData>  $links
     */
    public function __construct(
        public array $links,
    ) {}
}
