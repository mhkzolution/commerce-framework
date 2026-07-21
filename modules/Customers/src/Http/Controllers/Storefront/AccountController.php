<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Controllers\Storefront;

use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Customers\Contracts\CustomerAddressServiceInterface;
use Commerce\Customers\Contracts\CustomerAuthServiceInterface;
use Commerce\Customers\Contracts\CustomerServiceInterface;
use Commerce\Customers\DTO\CreateAddressData;
use Commerce\Customers\DTO\RegisterCustomerData;
use Commerce\Customers\DTO\UpdateCustomerData;
use Commerce\Customers\Http\Requests\StoreAddressRequest;
use Commerce\Customers\Http\Requests\StorefrontLoginRequest;
use Commerce\Customers\Http\Requests\StorefrontRegisterRequest;
use Commerce\Customers\Http\Requests\UpdateProfileRequest;
use Commerce\Customers\Services\CustomerAddressQueryService;
use Commerce\Customers\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class AccountController extends Controller
{
    public function __construct(
        private readonly CustomerAuthServiceInterface $authService,
        private readonly CustomerAddressQueryService $addressQueryService,
        private readonly CustomerAddressServiceInterface $addressService,
        private readonly CustomerServiceInterface $customerService,
        private readonly OrderQueryServiceInterface $orderQueryService,
    ) {}

    public function show(): View
    {
        $customer = $this->authService->current();
        abort_if($customer === null, 403);

        $orders = app()->bound(OrderQueryServiceInterface::class)
            ? $this->orderQueryService->paginateForCustomer($customer->uuid, 10)
            : null;

        return view('customers::storefront.account', [
            'customer' => $customer,
            'addresses' => $this->addressQueryService->forCustomer($customer->uuid),
            'orders' => $orders,
            'orderStatuses' => config('orders.statuses', []),
        ]);
    }

    public function showLogin(): View
    {
        return view('customers::storefront.login');
    }

    public function login(StorefrontLoginRequest $request): RedirectResponse
    {
        if (! $this->authService->attempt(
            $request->validated('email'),
            $request->validated('password'),
            (bool) $request->boolean('remember'),
        )) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        return redirect()->intended(route('storefront.account'));
    }

    public function showRegister(): View
    {
        return view('customers::storefront.register');
    }

    public function register(StorefrontRegisterRequest $request): RedirectResponse
    {
        $this->authService->register(new RegisterCustomerData(
            email: $request->validated('email'),
            name: $request->validated('name'),
            password: $request->validated('password'),
            phone: $request->validated('phone'),
        ));

        return redirect()->route('storefront.account');
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('storefront.shop.index');
    }

    public function storeAddress(StoreAddressRequest $request): RedirectResponse
    {
        $customer = $this->authService->current();
        abort_if($customer === null, 403);

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

    public function destroyAddress(string $address): RedirectResponse
    {
        $customer = $this->authService->current();
        abort_if($customer === null, 403);

        $model = $this->addressQueryService->findByUuid($address);
        abort_if($model === null || $model->customer_id !== $customer->id, 404);

        $this->addressService->delete($address);

        return back()->with('status', 'Address removed.');
    }

    public function showOrder(string $order): View
    {
        $customer = $this->authService->current();
        abort_if($customer === null, 403);

        $model = $this->orderQueryService->findByUuid($order);
        abort_if($model === null || $model->customer_uuid !== $customer->uuid, 404);

        return view('customers::storefront.order', [
            'order' => $model,
            'orderStatuses' => config('orders.statuses', []),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $customer = $this->authService->current();
        abort_if($customer === null, 403);

        $this->customerService->update($customer->uuid, new UpdateCustomerData(
            email: $request->validated('email'),
            name: $request->validated('name'),
            phone: $request->validated('phone'),
            status: $customer->status,
        ));

        $updated = Customer::query()->where('uuid', $customer->uuid)->first();
        if ($updated !== null) {
            Auth::guard('customer')->setUser($updated);
        }

        return back()->with('status', 'Profile updated.');
    }
}
