<?php

declare(strict_types=1);

namespace Commerce\Tax\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Tax\Contracts\TaxRateServiceInterface;
use Commerce\Tax\DTO\UpsertTaxRateData;
use Commerce\Tax\Models\TaxRate;

final class TaxRateService extends BaseService implements TaxRateServiceInterface
{
    public function create(UpsertTaxRateData $data): TaxRate
    {
        if (TaxRate::query()->where('code', $data->code)->exists()) {
            throw new DomainException('Tax rate code already exists.');
        }

        return TaxRate::query()->create([
            'code' => $data->code,
            'name' => $data->name,
            'rate_bps' => $data->rateBps,
            'country_code' => $data->countryCode ? strtoupper($data->countryCode) : null,
            'is_active' => $data->isActive,
            'priority' => $data->priority,
        ]);
    }

    public function update(string $uuid, UpsertTaxRateData $data): TaxRate
    {
        $rate = $this->findOrFail($uuid);

        if (TaxRate::query()->where('code', $data->code)->where('id', '!=', $rate->id)->exists()) {
            throw new DomainException('Tax rate code already exists.');
        }

        $rate->update([
            'code' => $data->code,
            'name' => $data->name,
            'rate_bps' => $data->rateBps,
            'country_code' => $data->countryCode ? strtoupper($data->countryCode) : null,
            'is_active' => $data->isActive,
            'priority' => $data->priority,
        ]);

        return $rate->fresh();
    }

    public function delete(string $uuid): void
    {
        $this->findOrFail($uuid)->delete();
    }

    private function findOrFail(string $uuid): TaxRate
    {
        $rate = TaxRate::query()->where('uuid', $uuid)->first();
        if ($rate === null) {
            throw new EntityNotFoundException("Tax rate [{$uuid}] not found.");
        }

        return $rate;
    }
}
