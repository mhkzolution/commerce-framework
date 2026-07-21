<?php

declare(strict_types=1);

namespace Commerce\Contracts\Purchasable;

interface PurchasableInterface
{
    public function getPurchasableUuid(): string;

    public function getSku(): ?string;

    public function isPurchasable(): bool;
}
