<?php

declare(strict_types=1);

namespace Commerce\Tax\Services;

use Commerce\Contracts\Tax\TaxCalculatorInterface;
use Commerce\Contracts\Tax\TaxQuoteServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Tax\DTO\TaxContext;

final class TaxQuoteService extends BaseService implements TaxQuoteServiceInterface
{
    public function __construct(private readonly TaxCalculatorInterface $calculator) {}

    public function calculate(int $taxableAmount, ?string $countryCode = null, string $currency = 'USD'): object
    {
        if ($taxableAmount <= 0) {
            return (object) ['total' => 0, 'lines' => []];
        }

        $context = new TaxContext(
            currency: $currency,
            lineItems: [['line_total' => $taxableAmount]],
            shippingAddress: $countryCode !== null ? ['country_code' => $countryCode] : [],
        );

        $lines = $this->calculator->calculate($context);
        $total = array_sum(array_map(static fn ($line) => $line->getAmount(), $lines));

        return (object) [
            'total' => $total,
            'lines' => array_map(static fn ($line): object => (object) [
                'label' => $line->getLabel(),
                'rate' => $line->getRate(),
                'amount' => $line->getAmount(),
                'currency' => $line->getCurrency(),
            ], $lines),
        ];
    }
}
