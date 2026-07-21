<?php

declare(strict_types=1);

namespace Commerce\Core\Pricing;

use Commerce\Contracts\Pricing\PricingContextInterface;

final class PricingContext implements PricingContextInterface
{
    public function __construct(
        private readonly string $channel = 'web',
        private readonly string $currency = 'USD',
        private readonly int $quantity = 1,
        private readonly ?string $customerUuid = null,
        private readonly ?string $couponCode = null,
    ) {}

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getCustomerUuid(): ?string
    {
        return $this->customerUuid;
    }

    public function getCouponCode(): ?string
    {
        return $this->couponCode;
    }
}
