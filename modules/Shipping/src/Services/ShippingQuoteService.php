<?php

declare(strict_types=1);

namespace Commerce\Shipping\Services;

use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Shipping\ShippingQuoteServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Shipping\Models\ShippingMethod;

final class ShippingQuoteService extends BaseService implements ShippingQuoteServiceInterface
{
    public function __construct(
        private readonly ShippingMethodQueryService $queryService,
    ) {}

    public function availableQuotes(int $subtotal, ?string $countryCode = null, string $currency = 'USD'): array
    {
        $quotes = [];

        foreach ($this->queryService->activeOrdered() as $method) {
            $quote = $this->buildQuote($method, $subtotal, $countryCode, $currency);

            if ($quote !== null) {
                $quotes[] = $quote;
            }
        }

        return $quotes;
    }

    public function resolveQuote(string $methodUuid, int $subtotal, ?string $countryCode = null, string $currency = 'USD'): ?object
    {
        $method = $this->queryService->findByUuid($methodUuid);

        if ($method === null) {
            return null;
        }

        return $this->buildQuote($method, $subtotal, $countryCode, $currency);
    }

    private function buildQuote(ShippingMethod $method, int $subtotal, ?string $countryCode, string $currency): ?object
    {
        if (! $method->isAvailableFor($subtotal, $countryCode)) {
            return null;
        }

        $price = $method->quotePrice($subtotal);

        if (app()->bound(CurrencyConverterInterface::class)) {
            $converter = app(CurrencyConverterInterface::class);
            $baseCurrency = $converter->baseCurrency();

            if ($currency !== $baseCurrency) {
                $price = $converter->convert($price, $baseCurrency, $currency);
            }
        }

        return (object) [
            'uuid' => $method->uuid,
            'code' => $method->code,
            'name' => $method->name,
            'description' => $method->description,
            'price' => $price,
            'currency' => $currency,
        ];
    }
}
