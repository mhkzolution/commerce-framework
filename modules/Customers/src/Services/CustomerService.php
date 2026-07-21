<?php

declare(strict_types=1);

namespace Commerce\Customers\Services;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Customers\Contracts\CustomerServiceInterface;
use Commerce\Customers\DTO\CreateCustomerData;
use Commerce\Customers\DTO\UpdateCustomerData;
use Commerce\Customers\Events\CustomerCreated;
use Commerce\Customers\Events\CustomerUpdated;
use Commerce\Customers\Models\Customer;
use Illuminate\Support\Facades\DB;

final class CustomerService extends BaseService implements CustomerServiceInterface
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {}

    public function create(CreateCustomerData $data): Customer
    {
        return DB::transaction(function () use ($data): Customer {
            if (Customer::query()->where('email', $data->email)->exists()) {
                throw new DomainException('A customer with this email already exists.');
            }

            $customer = Customer::query()->create([
                'email' => $data->email,
                'name' => $data->name,
                'phone' => $data->phone,
                'status' => $data->status,
            ]);

            $this->eventBus->dispatch(new CustomerCreated(
                customerUuid: $customer->uuid,
                email: $customer->email,
                tenantId: $customer->tenant_id,
            ));

            return $customer;
        });
    }

    public function update(string $uuid, UpdateCustomerData $data): Customer
    {
        return DB::transaction(function () use ($uuid, $data): Customer {
            $customer = $this->findOrFail($uuid);

            if (
                Customer::query()
                    ->where('email', $data->email)
                    ->where('id', '!=', $customer->id)
                    ->exists()
            ) {
                throw new DomainException('A customer with this email already exists.');
            }

            $customer->update([
                'email' => $data->email,
                'name' => $data->name,
                'phone' => $data->phone,
                'status' => $data->status,
            ]);

            $this->eventBus->dispatch(new CustomerUpdated(
                customerUuid: $customer->uuid,
                tenantId: $customer->tenant_id,
            ));

            return $customer->fresh();
        });
    }

    public function delete(string $uuid): void
    {
        $customer = $this->findOrFail($uuid);
        $customer->delete();
    }

    private function findOrFail(string $uuid): Customer
    {
        $customer = Customer::query()->where('uuid', $uuid)->first();

        if ($customer === null) {
            throw new EntityNotFoundException("Customer [{$uuid}] not found.");
        }

        return $customer;
    }
}
