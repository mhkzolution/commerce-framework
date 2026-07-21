<?php

declare(strict_types=1);

namespace Commerce\Currency\Services;

use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Currency\Models\Currency;

final class CurrencyConverterService extends BaseService implements CurrencyConverterInterface
{
    private const int RATE_PRECISION = 1_000_000;

    public function __construct(
        private readonly CurrencyQueryService $queryService,
    ) {}

    public function baseCurrency(): string
    {
        $base = $this->queryService->baseCurrency();

        return $base?->normalizedCode() ?? (string) config('cart.default_currency', 'USD');
    }

    public function activeCurrencies(): array
    {
        return $this->queryService->activeMap()
            ->map(fn (Currency $currency): object => (object) [
                'code' => $currency->normalizedCode(),
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'decimal_places' => (int) $currency->decimal_places,
                'rate_micro' => (int) $currency->rate_micro,
                'is_base' => (bool) $currency->is_base,
            ])
            ->values()
            ->all();
    }

    public function isSupported(string $code): bool
    {
        return $this->queryService->findByCode($code) !== null;
    }

    public function convert(int $amount, string $from, string $to): int
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return $amount;
        }

        $base = $this->baseCurrency();
        $baseAmount = $from === $base
            ? $amount
            : (int) round($amount * self::RATE_PRECISION / $this->rateMicroFor($from));

        if ($to === $base) {
            return $baseAmount;
        }

        return (int) round($baseAmount * $this->rateMicroFor($to) / self::RATE_PRECISION);
    }

    public function format(int $amount, string $currency): string
    {
        $model = $this->queryService->findByCode($currency);
        $decimals = $model?->decimal_places ?? 2;
        $symbol = $model?->symbol ?? strtoupper($currency);

        return $symbol . number_format($amount / (10 ** $decimals), $decimals);
    }

    private function rateMicroFor(string $code): int
    {
        $currency = $this->queryService->findByCode($code);

        if ($currency === null) {
            return self::RATE_PRECISION;
        }

        return max(1, (int) $currency->rate_micro);
    }
}
