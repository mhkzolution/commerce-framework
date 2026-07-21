<?php

declare(strict_types=1);

namespace Commerce\Tax\Contracts;

use Commerce\Tax\DTO\UpsertTaxRateData;
use Commerce\Tax\Models\TaxRate;

interface TaxRateServiceInterface
{
    public function create(UpsertTaxRateData $data): TaxRate;

    public function update(string $uuid, UpsertTaxRateData $data): TaxRate;

    public function delete(string $uuid): void;
}
