<?php

declare(strict_types=1);

namespace Commerce\Customers\Services;

use Commerce\Contracts\Customer\CustomerQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Customers\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Collection;

final class CustomerAddressQueryService extends BaseQueryService
{
    public function findByUuid(string $uuid): ?CustomerAddress
    {
        return CustomerAddress::query()->where('uuid', $uuid)->first();
    }

    /**
     * @return Collection<int, CustomerAddress>
     */
    public function forCustomer(string $customerUuid)
    {
        $customer = app(CustomerQueryServiceInterface::class)->findByUuid($customerUuid);

        if ($customer === null) {
            return collect();
        }

        return CustomerAddress::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('is_default_billing')
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();
    }

    public function countForCustomer(string $customerUuid): int
    {
        $customer = app(CustomerQueryServiceInterface::class)->findByUuid($customerUuid);

        if ($customer === null) {
            return 0;
        }

        return CustomerAddress::query()
            ->where('customer_id', $customer->id)
            ->count();
    }
}
