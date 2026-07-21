<?php

declare(strict_types=1);

namespace Commerce\Contracts\Currency;

interface CurrencyConverterInterface
{
    public function baseCurrency(): string;

    /**
     * @return list<object{
     *     code: string,
     *     name: string,
     *     symbol: string,
     *     decimal_places: int,
     *     rate_micro: int,
     *     is_base: bool
     * }>
     */
    public function activeCurrencies(): array;

    public function isSupported(string $code): bool;

    public function convert(int $amount, string $from, string $to): int;

    public function format(int $amount, string $currency): string;
}
