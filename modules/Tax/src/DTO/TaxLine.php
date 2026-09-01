<?php

declare(strict_types=1);

namespace Commerce\Tax\DTO;

use Commerce\Contracts\Tax\TaxLineInterface;

final class TaxLine implements TaxLineInterface
{
    public function __construct(
        private readonly string $label,
        private readonly float $rate,
        private readonly int $amount,
        private readonly string $currency,
    ) {}

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
