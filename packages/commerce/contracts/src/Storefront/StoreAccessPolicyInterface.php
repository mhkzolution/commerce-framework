<?php

declare(strict_types=1);

namespace Commerce\Contracts\Storefront;

interface StoreAccessPolicyInterface
{
    public function canAccessStore(?object $customer): bool;
}
