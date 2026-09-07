<?php

declare(strict_types=1);

namespace Commerce\Customers\Services;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Customers\Contracts\CustomerAuthServiceInterface;
use Commerce\Customers\DTO\RegisterCustomerData;
use Commerce\Customers\Events\CustomerCreated;
use Commerce\Customers\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class CustomerAuthService extends BaseService implements CustomerAuthServiceInterface
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
    ) {}

    public function register(RegisterCustomerData $data): Customer
    {
        return DB::transaction(function () use ($data): Customer {
            if (Customer::query()->where('email', $data->email)->exists()) {
                throw new DomainException('An account with this email already exists.');
            }

            $customer = Customer::query()->create([
                'email' => $data->email,
                'name' => $data->name,
                'phone' => $data->phone,
                'password' => $data->password,
                'status' => 'active',
            ]);

            $this->eventBus->dispatch(new CustomerCreated(
                customerUuid: $customer->uuid,
                email: $customer->email,
                tenantId: $customer->tenant_id,
            ));

            Auth::guard('customer')->login($customer);

            return $customer;
        });
    }

    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        return Auth::guard('customer')->attempt(['email' => $email, 'password' => $password], $remember);
    }

    public function logout(): void
    {
        Auth::guard('customer')->logout();
    }

    public function current(): ?Customer
    {
        $user = Auth::guard('customer')->user();

        return $user instanceof Customer ? $user : null;
    }

    public function changePassword(Customer $customer, string $password): void
    {
        $customer->update(['password' => $password]);
    }
}
