<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Controllers\Admin;

use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Customers\Services\CustomerAddressQueryService;
use Commerce\Customers\Contracts\CustomerServiceInterface;
use Commerce\Customers\DTO\CreateCustomerData;
use Commerce\Customers\DTO\UpdateCustomerData;
use Commerce\Customers\Http\Requests\StoreCustomerRequest;
use Commerce\Customers\Http\Requests\UpdateCustomerRequest;
use Commerce\Customers\Models\Customer;
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
        ));

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
}
