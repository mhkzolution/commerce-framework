<?php

declare(strict_types=1);

namespace Commerce\Cart\Contracts;

use Commerce\Cart\DTO\CartData;
use Commerce\Cart\DTO\CartLineData;

interface CartServiceInterface
{
    public function get(): CartData;

    public function add(CartLineData $line): CartData;

    public function update(string $purchasableUuid, int $quantity): CartData;

    public function remove(string $purchasableUuid): CartData;

    public function applyCoupon(string $code): CartData;

    public function removeCoupon(): CartData;

    public function clear(): void;

    public function setCurrency(string $currency): CartData;
}
