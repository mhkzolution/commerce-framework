<?php

declare(strict_types=1);

namespace Commerce\Contracts\ValueObject;

interface MoneyInterface
{
    public function getAmount(): int;

    public function getCurrency(): string;
}
