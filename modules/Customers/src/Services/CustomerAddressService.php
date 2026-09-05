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
            [$isDefaultShipping, $isDefaultBilling] = $this->resolvedDefaultFlags($data);

            if ($isDefaultShipping) {
                $this->clearDefaultShipping($customer->id);
            }

            if ($isDefaultBilling) {
                $this->clearDefaultBilling($customer->id);
            }

            return CustomerAddress::query()->create([
                'customer_id' => $customer->id,
                'label' => $data->label,
                'type' => $data->type,
                'line1' => $data->line1,
                'line2' => $data->line2,
                'city' => $data->city,
                'district' => $data->district,
                'subdistrict' => $data->subdistrict,
                'state' => $data->state,
                'postal_code' => $data->postalCode,
                'country_code' => $data->countryCode,
                'is_default' => $isDefaultShipping || $isDefaultBilling,
                'is_default_shipping' => $isDefaultShipping,
                'is_default_billing' => $isDefaultBilling,
            ]);
        });
    }

    public function update(string $uuid, CreateAddressData $data): CustomerAddress
    {
        return DB::transaction(function () use ($uuid, $data): CustomerAddress {
            $address = CustomerAddress::query()->where('uuid', $uuid)->first();

            if ($address === null) {
                throw new EntityNotFoundException("Address [{$uuid}] not found.");
            }

            $customer = Customer::query()->where('uuid', $data->customerUuid)->firstOrFail();

            if ($address->customer_id !== $customer->id) {
                throw new EntityNotFoundException("Address [{$uuid}] not found.");
            }

            [$isDefaultShipping, $isDefaultBilling] = $this->resolvedDefaultFlags($data);

            if ($isDefaultShipping) {
                $this->clearDefaultShipping($address->customer_id);
            }

            if ($isDefaultBilling) {
                $this->clearDefaultBilling($address->customer_id);
            }

            $address->update([
                'label' => $data->label,
                'type' => $data->type,
                'line1' => $data->line1,
                'line2' => $data->line2,
                'city' => $data->city,
                'district' => $data->district,
                'subdistrict' => $data->subdistrict,
                'state' => $data->state,
                'postal_code' => $data->postalCode,
                'country_code' => $data->countryCode,
                'is_default' => $isDefaultShipping || $isDefaultBilling || $address->is_default_shipping || $address->is_default_billing,
                'is_default_shipping' => $isDefaultShipping || $address->is_default_shipping,
                'is_default_billing' => $isDefaultBilling || $address->is_default_billing,
            ]);

            return $address->fresh();
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

            if (in_array($address->type, ['shipping', 'both'], true)) {
                $this->markDefaultShipping($address);
            }

            if (in_array($address->type, ['billing', 'both'], true)) {
                $this->markDefaultBilling($address);
            }

            return $address->fresh();
        });
    }

    public function setDefaultShipping(string $uuid): CustomerAddress
    {
        return DB::transaction(function () use ($uuid): CustomerAddress {
            $address = CustomerAddress::query()->where('uuid', $uuid)->firstOrFail();
            $this->markDefaultShipping($address);

            return $address->fresh();
        });
    }

    public function setDefaultBilling(string $uuid): CustomerAddress
    {
        return DB::transaction(function () use ($uuid): CustomerAddress {
            $address = CustomerAddress::query()->where('uuid', $uuid)->firstOrFail();
            $this->markDefaultBilling($address);

            return $address->fresh();
        });
    }

    /**
     * @return array{0: bool, 1: bool}
     */
    private function resolvedDefaultFlags(CreateAddressData $data): array
    {
        $isDefaultShipping = $data->isDefaultShipping
            || ($data->isDefault && in_array($data->type, ['shipping', 'both'], true));
        $isDefaultBilling = $data->isDefaultBilling
            || ($data->isDefault && in_array($data->type, ['billing', 'both'], true));

        return [$isDefaultShipping, $isDefaultBilling];
    }

    private function markDefaultShipping(CustomerAddress $address): void
    {
        $this->clearDefaultShipping($address->customer_id);
        $address->update([
            'is_default_shipping' => true,
            'is_default' => true,
        ]);
    }

    private function markDefaultBilling(CustomerAddress $address): void
    {
        $this->clearDefaultBilling($address->customer_id);
        $address->update([
            'is_default_billing' => true,
            'is_default' => true,
        ]);
    }

    private function clearDefaultShipping(int $customerId): void
    {
        CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->update(['is_default_shipping' => false]);

        $this->syncLegacyDefaultFlag($customerId);
    }

    private function clearDefaultBilling(int $customerId): void
    {
        CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->update(['is_default_billing' => false]);

        $this->syncLegacyDefaultFlag($customerId);
    }

    private function syncLegacyDefaultFlag(int $customerId): void
    {
        CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->where('is_default_shipping', false)
            ->where('is_default_billing', false)
            ->update(['is_default' => false]);
    }
}
