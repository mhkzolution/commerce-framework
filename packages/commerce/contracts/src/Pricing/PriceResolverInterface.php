<?php

declare(strict_types=1);

namespace Commerce\Contracts\Pricing;

interface PriceResolverInterface
{
    public function resolve(\Commerce\Contracts\Purchasable\PurchasableInterface $purchasable, PricingContextInterface $context): PriceQuoteInterface;
}
