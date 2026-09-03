<?php

declare(strict_types=1);

namespace Commerce\Contracts\Navigation;

interface NavigationQueryServiceInterface
{
    /**
     * @return list<NavigationLinkData>
     */
    public function links(string $source): array;
}
