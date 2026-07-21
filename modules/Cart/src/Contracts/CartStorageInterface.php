<?php

declare(strict_types=1);

namespace Commerce\Cart\Contracts;

interface CartStorageInterface
{
    /**
     * @return list<array{purchasable_uuid: string, quantity: int}>
     */
    public function lines(): array;

    /**
     * @param  list<array{purchasable_uuid: string, quantity: int}>  $lines
     */
    public function put(array $lines): void;

    public function clear(): void;

    public function currency(): string;

    public function setCurrency(string $currency): void;

    public function couponCode(): ?string;

    public function setCouponCode(?string $code): void;
}
