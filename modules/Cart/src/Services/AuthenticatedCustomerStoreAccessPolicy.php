<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Contracts\Storefront\StoreAccessPolicyInterface;
use Commerce\Customers\Models\Customer;

final class AuthenticatedCustomerStoreAccessPolicy implements StoreAccessPolicyInterface
{
    public function canAccessStore(?object $customer): bool
    {
        if (! $customer instanceof Customer) {
            return false;
        }

        return $customer->isActive();
    }
}
