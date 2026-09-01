<?php

declare(strict_types=1);

namespace Commerce\Wishlist;

use Commerce\Contracts\Module\ModuleInterface;

final class WishlistModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Wishlist';
    }

    public function getAlias(): string
    {
        return 'wishlist';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getPriority(): int
    {
        return 29;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getSoftDependencies(): array
    {
        return ['customers', 'product'];
    }
}
