<?php

declare(strict_types=1);

namespace Commerce\Contracts\Tax;

interface TaxQuoteServiceInterface
{
    /**
     * @return object{total: int, lines: list<object{label: string, rate: float, amount: int, currency: string}>}
     */
    public function calculate(int $taxableAmount, ?string $countryCode = null, string $currency = 'USD'): object;
}
