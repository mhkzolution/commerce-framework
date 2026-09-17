<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Controllers\Admin;

use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Customers\Contracts\CustomerAddressServiceInterface;
use Commerce\Customers\Contracts\CustomerServiceInterface;
use Commerce\Customers\DTO\CreateAddressData;
use Commerce\Customers\DTO\CreateCustomerData;
use Commerce\Customers\DTO\UpdateCustomerData;
use Commerce\Customers\Http\Requests\StoreCustomerRequest;
use Commerce\Customers\Http\Requests\UpdateCustomerRequest;
use Commerce\Customers\Models\Customer;
use Commerce\Customers\Services\CustomerAddressQueryService;
use Commerce\Customers\Services\CustomerQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerQueryService $queryService,
        private readonly CustomerServiceInterface $customerService,
        private readonly CustomerAddressServiceInterface $addressService,
    ) {}

    public function index(Request $request): View
    {
        return view('customers::admin.index', [
            'customers' => $this->queryService->paginate(
                search: $request->string('search')->toString() ?: null,
                status: $request->string('status')->toString() ?: null,
            ),
            'statuses' => config('customers.statuses', []),
        ]);
    }

    public function create(): View
    {
        return view('customers::admin.create', [
            'statuses' => config('customers.statuses', []),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = $this->customerService->create(new CreateCustomerData(
            email: $request->validated('email'),
            name: $request->validated('name'),
            phone: $request->validated('phone'),
            status: $request->validated('status'),
            password: $request->validated('password'),
        ));

        $this->createAddressIfPresent($customer, $request->validated('address') ?? []);

        return redirect()
            ->route('admin.customers.edit', $customer)
            ->with('status', 'Customer created.');
    }

    public function edit(Customer $customer): View
    {
        $orders = null;
        $orderStatuses = [];

        if (app()->bound(OrderQueryServiceInterface::class)) {
            $orders = app(OrderQueryServiceInterface::class)->paginateForCustomer($customer->uuid);
            $orderStatuses = config('orders.statuses', []);
        }

        return view('customers::admin.edit', [
            'customer' => $customer,
            'statuses' => config('customers.statuses', []),
            'addresses' => app(CustomerAddressQueryService::class)->forCustomer($customer->uuid),
            'orders' => $orders,
            'orderStatuses' => $orderStatuses,
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->customerService->update($customer->uuid, new UpdateCustomerData(
            email: $request->validated('email'),
            name: $request->validated('name'),
            phone: $request->validated('phone'),
            status: $request->validated('status'),
            password: $request->validated('password'),
        ));

        return redirect()
            ->route('admin.customers.edit', $customer)
            ->with('status', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->customerService->delete($customer->uuid);

        return redirect()
            ->route('admin.customers.index')
            ->with('status', 'Customer deleted.');
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function createAddressIfPresent(Customer $customer, array $address): void
    {
        $line1 = isset($address['line1']) ? trim((string) $address['line1']) : '';
        if ($line1 === '') {
            return;
        }

        $this->addressService->create(new CreateAddressData(
            customerUuid: $customer->uuid,
            line1: $line1,
            city: (string) ($address['city'] ?? $address['district'] ?? ''),
            postalCode: (string) ($address['postal_code'] ?? ''),
            countryCode: strtoupper((string) ($address['country_code'] ?? 'TH')),
            type: 'both',
            line2: isset($address['line2']) ? (string) $address['line2'] : null,
            state: isset($address['state']) ? (string) $address['state'] : null,
            district: isset($address['district']) ? (string) $address['district'] : null,
            subdistrict: isset($address['subdistrict']) ? (string) $address['subdistrict'] : null,
            isDefault: true,
            isDefaultShipping: true,
            isDefaultBilling: true,
        ));
    }
}
