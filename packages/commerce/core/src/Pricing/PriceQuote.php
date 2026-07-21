<?php

declare(strict_types=1);

namespace Commerce\Core\Pricing;

use Commerce\Contracts\Pricing\PriceQuoteInterface;

final class PriceQuote implements PriceQuoteInterface
{
    /**
     * @param  array<string, mixed>  $breakdown
     */
    public function __construct(
        private readonly int $amount,
        private readonly string $currency,
        private readonly array $breakdown = [],
    ) {}

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getBreakdown(): array
    {
        return $this->breakdown;
    }
}
