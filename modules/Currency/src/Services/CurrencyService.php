<?php

declare(strict_types=1);

namespace Commerce\Currency\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Currency\Contracts\CurrencyServiceInterface;
use Commerce\Currency\DTO\CreateCurrencyData;
use Commerce\Currency\DTO\UpdateCurrencyData;
use Commerce\Currency\Models\Currency;
use Illuminate\Support\Facades\DB;

final class CurrencyService extends BaseService implements CurrencyServiceInterface
{
    public function __construct(
        private readonly CurrencyQueryService $queryService,
    ) {}

    public function create(CreateCurrencyData $data): Currency
    {
        if (Currency::query()->where('code', $data->code)->exists()) {
            throw new DomainException('Currency code already exists.');
        }

        return DB::transaction(function () use ($data): Currency {
            if ($data->isBase) {
                $this->clearOtherBaseCurrencies();
            }

            $currency = Currency::query()->create([
                'code' => $data->code,
                'name' => $data->name,
                'symbol' => $data->symbol,
                'decimal_places' => $data->decimalPlaces,
                'rate_micro' => $data->isBase ? 1_000_000 : $data->rateMicro,
                'is_base' => $data->isBase,
                'is_active' => $data->isActive,
                'sort_order' => $data->sortOrder,
            ]);

            $this->queryService->clearCache();

            return $currency;
        });
    }

    public function update(string $uuid, UpdateCurrencyData $data): Currency
    {
        $currency = $this->findOrFail($uuid);

        if (Currency::query()->where('code', $data->code)->where('id', '!=', $currency->id)->exists()) {
            throw new DomainException('Currency code already exists.');
        }

        return DB::transaction(function () use ($currency, $data): Currency {
            if ($data->isBase) {
                $this->clearOtherBaseCurrencies($currency->id);
            }

            if ($currency->is_base && ! $data->isBase) {
                throw new DomainException('Store must have a base currency.');
            }

            $currency->update([
                'code' => $data->code,
                'name' => $data->name,
                'symbol' => $data->symbol,
                'decimal_places' => $data->decimalPlaces,
                'rate_micro' => $data->isBase ? 1_000_000 : $data->rateMicro,
                'is_base' => $data->isBase,
                'is_active' => $data->isActive,
                'sort_order' => $data->sortOrder,
            ]);

            $this->queryService->clearCache();

            return $currency->fresh();
        });
    }

    public function delete(string $uuid): void
    {
        $currency = $this->findOrFail($uuid);

        if ($currency->is_base) {
            throw new DomainException('Base currency cannot be deleted.');
        }

        $currency->delete();
        $this->queryService->clearCache();
    }

    private function clearOtherBaseCurrencies(?int $exceptId = null): void
    {
        Currency::query()
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('is_base', true)
            ->update([
                'is_base' => false,
            ]);
    }

    private function findOrFail(string $uuid): Currency
    {
        $currency = Currency::query()->where('uuid', $uuid)->first();

        if ($currency === null) {
            throw new EntityNotFoundException("Currency [{$uuid}] not found.");
        }

        return $currency;
    }
}
