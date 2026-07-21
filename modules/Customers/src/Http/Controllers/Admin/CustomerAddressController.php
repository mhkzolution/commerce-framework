<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Controllers\Admin;

use Commerce\Customers\Contracts\CustomerAddressServiceInterface;
use Commerce\Customers\DTO\CreateAddressData;
use Commerce\Customers\Http\Requests\StoreAddressRequest;
use Commerce\Customers\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class CustomerAddressController extends Controller
{
    public function __construct(
        private readonly CustomerAddressServiceInterface $addressService,
    ) {}

    public function store(StoreAddressRequest $request, Customer $customer): RedirectResponse
    {
        $this->addressService->create(new CreateAddressData(
            customerUuid: $customer->uuid,
            line1: $request->validated('line1'),
            city: $request->validated('city'),
            postalCode: $request->validated('postal_code'),
            countryCode: $request->validated('country_code'),
            type: $request->validated('type'),
            label: $request->validated('label'),
            line2: $request->validated('line2'),
            state: $request->validated('state'),
            isDefault: (bool) $request->boolean('is_default'),
        ));

        return back()->with('status', 'Address added.');
    }

    public function destroy(Customer $customer, string $address): RedirectResponse
    {
        $this->addressService->delete($address);

        return back()->with('status', 'Address removed.');
    }
}
