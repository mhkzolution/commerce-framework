<?php

declare(strict_types=1);

namespace Commerce\Currency\Contracts;

use Commerce\Currency\DTO\CreateCurrencyData;
use Commerce\Currency\DTO\UpdateCurrencyData;
use Commerce\Currency\Models\Currency;

interface CurrencyServiceInterface
{
    public function create(CreateCurrencyData $data): Currency;

    public function update(string $uuid, UpdateCurrencyData $data): Currency;

    public function delete(string $uuid): void;
}
