<?php

declare(strict_types=1);

namespace Commerce\Shipping\Contracts;

use Commerce\Shipping\DTO\CreateShippingMethodData;
use Commerce\Shipping\DTO\UpdateShippingMethodData;
use Commerce\Shipping\Models\ShippingMethod;

interface ShippingMethodServiceInterface
{
    public function create(CreateShippingMethodData $data): ShippingMethod;

    public function update(string $uuid, UpdateShippingMethodData $data): ShippingMethod;

    public function delete(string $uuid): void;
}
