<?php

declare(strict_types=1);

namespace Commerce\Core\Support;

use Commerce\Contracts\ValueObject\MoneyInterface;

final readonly class Money implements MoneyInterface
{
    public function __construct(
        private int $amount,
        private string $currency,
    ) {}

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
