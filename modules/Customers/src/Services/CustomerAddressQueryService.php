<?php

declare(strict_types=1);

namespace Commerce\Customers\Services;

use Commerce\Contracts\Customer\CustomerQueryServiceInterface;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Customers\Models\CustomerAddress;

final class CustomerAddressQueryService extends BaseQueryService
{
    public function findByUuid(string $uuid): ?CustomerAddress
    {
        return CustomerAddress::query()->where('uuid', $uuid)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, CustomerAddress>
     */
    public function forCustomer(string $customerUuid)
    {
        $customer = app(CustomerQueryServiceInterface::class)->findByUuid($customerUuid);

        if ($customer === null) {
            return collect();
        }

        return CustomerAddress::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();
    }
}
