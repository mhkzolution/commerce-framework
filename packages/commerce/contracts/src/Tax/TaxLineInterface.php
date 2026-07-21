<?php

declare(strict_types=1);

namespace Commerce\Contracts\Tax;

interface TaxLineInterface
{
    public function getLabel(): string;

    public function getRate(): float;

    public function getAmount(): int;

    public function getCurrency(): string;
}
