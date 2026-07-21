<?php

declare(strict_types=1);

namespace Commerce\Contracts\Tax;

interface TaxContextInterface
{
    public function getCurrency(): string;

    /**
     * @return list<array<string, mixed>>
     */
    public function getLineItems(): array;

    /**
     * @return array<string, mixed>
     */
    public function getShippingAddress(): array;

    /**
     * @return array<string, mixed>
     */
    public function getBillingAddress(): array;
}
