<?php

declare(strict_types=1);

namespace Commerce\Customers\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Customers\Contracts\CustomerAddressServiceInterface;
use Commerce\Customers\DTO\CreateAddressData;
use Commerce\Customers\Models\Customer;
use Commerce\Customers\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;

final class CustomerAddressService extends BaseService implements CustomerAddressServiceInterface
{
    public function create(CreateAddressData $data): CustomerAddress
    {
        return DB::transaction(function () use ($data): CustomerAddress {
            $customer = Customer::query()->where('uuid', $data->customerUuid)->firstOrFail();

            if ($data->isDefault) {
                CustomerAddress::query()
                    ->where('customer_id', $customer->id)
                    ->where('type', $data->type)
                    ->update(['is_default' => false]);
            }

            return CustomerAddress::query()->create([
                'customer_id' => $customer->id,
                'label' => $data->label,
                'type' => $data->type,
                'line1' => $data->line1,
                'line2' => $data->line2,
                'city' => $data->city,
                'state' => $data->state,
                'postal_code' => $data->postalCode,
                'country_code' => $data->countryCode,
                'is_default' => $data->isDefault,
            ]);
        });
    }

    public function delete(string $uuid): void
    {
        $address = CustomerAddress::query()->where('uuid', $uuid)->first();

        if ($address === null) {
            throw new EntityNotFoundException("Address [{$uuid}] not found.");
        }

        $address->delete();
    }

    public function setDefault(string $uuid): CustomerAddress
    {
        return DB::transaction(function () use ($uuid): CustomerAddress {
            $address = CustomerAddress::query()->where('uuid', $uuid)->firstOrFail();

            CustomerAddress::query()
                ->where('customer_id', $address->customer_id)
                ->where('type', $address->type)
                ->update(['is_default' => false]);

            $address->update(['is_default' => true]);

            return $address->fresh();
        });
    }
}
