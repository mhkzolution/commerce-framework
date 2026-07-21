<?php

declare(strict_types=1);

namespace Commerce\Contracts\Pricing;

interface PriceQuoteInterface
{
    public function getAmount(): int;

    public function getCurrency(): string;

    /**
     * @return array<string, mixed>
     */
    public function getBreakdown(): array;
}
