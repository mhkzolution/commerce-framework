<?php

declare(strict_types=1);

namespace Commerce\Contracts\Shipping;

interface ShippingQuoteServiceInterface
{
    /**
     * @return list<object{
     *     uuid: string,
     *     code: string,
     *     name: string,
     *     description: ?string,
     *     price: int,
     *     currency: string
     * }>
     */
    public function availableQuotes(int $subtotal, ?string $countryCode = null, string $currency = 'USD'): array;

    /**
     * @return object{
     *     uuid: string,
     *     code: string,
     *     name: string,
     *     description: ?string,
     *     price: int,
     *     currency: string
     * }|null
     */
    public function resolveQuote(string $methodUuid, int $subtotal, ?string $countryCode = null, string $currency = 'USD'): ?object;
}
