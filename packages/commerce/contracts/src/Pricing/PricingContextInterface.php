<?php

declare(strict_types=1);

namespace Commerce\Contracts\Pricing;

interface PricingContextInterface
{
    public function getChannel(): string;

    public function getCurrency(): string;

    public function getQuantity(): int;

    public function getCustomerUuid(): ?string;
}
