<?php

declare(strict_types=1);

namespace Commerce\Tax\Services;

use Commerce\Contracts\Tax\TaxQuoteServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Tax\Models\TaxRate;

final class TaxQuoteService extends BaseService implements TaxQuoteServiceInterface
{
    public function calculate(int $taxableAmount, ?string $countryCode = null, string $currency = 'USD'): object
    {
        if ($taxableAmount <= 0) {
            return (object) ['total' => 0, 'lines' => []];
        }

        $rates = TaxRate::query()
            ->where('is_active', true)
            ->when($countryCode, fn ($q) => $q->where(fn ($inner) => $inner
                ->whereNull('country_code')
                ->orWhere('country_code', strtoupper($countryCode))))
            ->when(! $countryCode, fn ($q) => $q->whereNull('country_code'))
            ->orderByDesc('priority')
            ->get();

        $lines = [];
        $total = 0;

        foreach ($rates as $rate) {
            $amount = $rate->calculate($taxableAmount);
            if ($amount <= 0) {
                continue;
            }

            $lines[] = (object) [
                'label' => $rate->name,
                'rate' => $rate->ratePercent(),
                'amount' => $amount,
                'currency' => $currency,
            ];
            $total += $amount;
        }

        return (object) ['total' => $total, 'lines' => $lines];
    }
}
