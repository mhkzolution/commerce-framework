<?php

declare(strict_types=1);

namespace Commerce\Tax\Services;

use Commerce\Contracts\Tax\TaxCalculatorInterface;
use Commerce\Contracts\Tax\TaxContextInterface;
use Commerce\Tax\DTO\TaxContext;
use Commerce\Tax\DTO\TaxLine;
use Commerce\Tax\Models\TaxRate;

final class DatabaseTaxCalculator implements TaxCalculatorInterface
{
    public function calculate(TaxContextInterface $context): array
    {
        $taxableAmount = $context instanceof TaxContext
            ? $context->taxableAmount()
            : $this->resolveTaxableAmount($context);

        if ($taxableAmount <= 0) {
            return [];
        }

        $countryCode = $context instanceof TaxContext
            ? $context->countryCode()
            : $this->resolveCountryCode($context);

        $rates = TaxRate::query()
            ->where('is_active', true)
            ->when($countryCode, fn ($q) => $q->where(fn ($inner) => $inner
                ->whereNull('country_code')
                ->orWhere('country_code', $countryCode)))
            ->when(! $countryCode, fn ($q) => $q->whereNull('country_code'))
            ->orderByDesc('priority')
            ->get();

        $lines = [];

        foreach ($rates as $rate) {
            $amount = $rate->calculate($taxableAmount);

            if ($amount <= 0) {
                continue;
            }

            $lines[] = new TaxLine(
                label: $rate->name,
                rate: $rate->ratePercent(),
                amount: $amount,
                currency: $context->getCurrency(),
            );
        }

        return $lines;
    }

    private function resolveTaxableAmount(TaxContextInterface $context): int
    {
        $total = 0;

        foreach ($context->getLineItems() as $line) {
            $total += (int) ($line['line_total'] ?? $line['amount'] ?? 0);
        }

        return max(0, $total);
    }

    private function resolveCountryCode(TaxContextInterface $context): ?string
    {
        $address = $context->getShippingAddress() !== []
            ? $context->getShippingAddress()
            : $context->getBillingAddress();

        $country = $address['country_code'] ?? $address['country'] ?? null;

        return is_string($country) && $country !== '' ? strtoupper($country) : null;
    }
}
