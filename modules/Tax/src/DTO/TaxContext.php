<?php

declare(strict_types=1);

namespace Commerce\Tax\DTO;

use Commerce\Contracts\Tax\TaxContextInterface;

final class TaxContext implements TaxContextInterface
{
    /**
     * @param  list<array<string, mixed>>  $lineItems
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<string, mixed>  $billingAddress
     */
    public function __construct(
        private readonly string $currency = 'USD',
        private readonly array $lineItems = [],
        private readonly array $shippingAddress = [],
        private readonly array $billingAddress = [],
    ) {}

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function getShippingAddress(): array
    {
        return $this->shippingAddress;
    }

    public function getBillingAddress(): array
    {
        return $this->billingAddress;
    }

    public function taxableAmount(): int
    {
        $total = 0;

        foreach ($this->lineItems as $line) {
            $total += (int) ($line['line_total'] ?? $line['amount'] ?? 0);
        }

        return max(0, $total);
    }

    public function countryCode(): ?string
    {
        $address = $this->shippingAddress !== [] ? $this->shippingAddress : $this->billingAddress;

        $country = $address['country_code'] ?? $address['country'] ?? null;

        return is_string($country) && $country !== '' ? strtoupper($country) : null;
    }
}
