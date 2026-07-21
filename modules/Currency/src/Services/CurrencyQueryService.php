<?php

declare(strict_types=1);

namespace Commerce\Currency\Services;

use Commerce\Currency\Models\Currency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class CurrencyQueryService
{
    private const string CACHE_KEY = 'currency.active_map.v2';

    public function paginate(?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return Currency::query()
            ->when($search, function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_base')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<string, Currency>
     */
    public function activeMap(): Collection
    {
        $cached = Cache::remember(self::CACHE_KEY, 300, function (): array {
            return Currency::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get()
                ->mapWithKeys(fn (Currency $currency): array => [
                    $currency->normalizedCode() => $currency->getAttributes(),
                ])
                ->all();
        });

        if (! is_array($cached)) {
            Cache::forget(self::CACHE_KEY);

            return $this->activeMap();
        }

        return collect($cached)->map(function (array $attributes): Currency {
            $currency = new Currency;
            $currency->forceFill($attributes);

            if (isset($attributes['id'])) {
                $currency->exists = true;
            }

            return $currency->syncOriginal();
        });
    }

    public function findByCode(string $code): ?Currency
    {
        return $this->activeMap()->get(strtoupper($code));
    }

    public function baseCurrency(): ?Currency
    {
        return $this->activeMap()->first(fn (Currency $currency): bool => $currency->is_base)
            ?? Currency::query()->where('is_base', true)->first();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('currency.active_map');
    }
}
