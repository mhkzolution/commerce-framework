<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Contracts\Storefront\StoreAccessPolicyInterface;
use Commerce\Contracts\Storefront\StorefrontAccessContext;
use Commerce\Contracts\Storefront\StoreVisibility;
use Commerce\Settings\Services\StoreVisibilityConfig;
use Illuminate\Support\Facades\Auth;

final class StorefrontAccessResolver
{
    public function __construct(
        private readonly StoreVisibilityConfig $visibility,
        private readonly StoreAccessPolicyInterface $policy,
    ) {}

    public function resolve(?object $customer = null): StorefrontAccessContext
    {
        $mode = $this->visibility->mode();
        $customer ??= Auth::guard('customer')->user();
        $authenticated = $customer !== null;
        $entitled = $authenticated && $this->policy->canAccessStore($customer);

        return match ($mode) {
            StoreVisibility::Public => new StorefrontAccessContext(
                mode: $mode,
                authenticated: $authenticated,
                entitled: $entitled,
                canViewCatalog: true,
                canViewPrices: true,
                canPurchase: true,
            ),
            StoreVisibility::Catalog => new StorefrontAccessContext(
                mode: $mode,
                authenticated: $authenticated,
                entitled: $entitled,
                canViewCatalog: true,
                canViewPrices: $authenticated,
                canPurchase: $authenticated,
            ),
            StoreVisibility::Members => new StorefrontAccessContext(
                mode: $mode,
                authenticated: $authenticated,
                entitled: $entitled,
                canViewCatalog: $authenticated,
                canViewPrices: $authenticated,
                canPurchase: $authenticated,
            ),
            StoreVisibility::Private => new StorefrontAccessContext(
                mode: $mode,
                authenticated: $authenticated,
                entitled: $entitled,
                canViewCatalog: $entitled,
                canViewPrices: $entitled,
                canPurchase: $entitled,
            ),
        };
    }
}
