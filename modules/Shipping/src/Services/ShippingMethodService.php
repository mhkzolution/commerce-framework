<?php

declare(strict_types=1);

namespace Commerce\Shipping\Services;

use Commerce\Contracts\Shipping\ShippingQuoteServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Shipping\Contracts\ShippingMethodServiceInterface;
use Commerce\Shipping\DTO\CreateShippingMethodData;
use Commerce\Shipping\DTO\UpdateShippingMethodData;
use Commerce\Shipping\Models\ShippingMethod;

final class ShippingMethodService extends BaseService implements ShippingMethodServiceInterface
{
    public function create(CreateShippingMethodData $data): ShippingMethod
    {
        if (ShippingMethod::query()->where('code', $data->code)->exists()) {
            throw new DomainException('Shipping method code already exists.');
        }

        return ShippingMethod::query()->create([
            'code' => $data->code,
            'name' => $data->name,
            'description' => $data->description,
            'price' => $data->price,
            'free_above' => $data->freeAbove,
            'min_subtotal' => $data->minSubtotal,
            'max_subtotal' => $data->maxSubtotal,
            'countries' => $data->countries,
            'is_active' => $data->isActive,
            'sort_order' => $data->sortOrder,
        ]);
    }

    public function update(string $uuid, UpdateShippingMethodData $data): ShippingMethod
    {
        $method = $this->findOrFail($uuid);

        if (ShippingMethod::query()->where('code', $data->code)->where('id', '!=', $method->id)->exists()) {
            throw new DomainException('Shipping method code already exists.');
        }

        $method->update([
            'code' => $data->code,
            'name' => $data->name,
            'description' => $data->description,
            'price' => $data->price,
            'free_above' => $data->freeAbove,
            'min_subtotal' => $data->minSubtotal,
            'max_subtotal' => $data->maxSubtotal,
            'countries' => $data->countries,
            'is_active' => $data->isActive,
            'sort_order' => $data->sortOrder,
        ]);

        return $method->fresh();
    }

    public function delete(string $uuid): void
    {
        $this->findOrFail($uuid)->delete();
    }

    private function findOrFail(string $uuid): ShippingMethod
    {
        $method = ShippingMethod::query()->where('uuid', $uuid)->first();

        if ($method === null) {
            throw new EntityNotFoundException("Shipping method [{$uuid}] not found.");
        }

        return $method;
    }
}
