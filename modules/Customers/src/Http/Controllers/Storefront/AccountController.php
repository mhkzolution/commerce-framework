<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Controllers\Storefront;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
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
use Commerce\Customers\Models\Customer;
use Commerce\Customers\Services\CustomerAddressQueryService;
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
        return view('customers::storefront.login', $this->authPageData());
    }

    public function login(StorefrontLoginRequest $request): RedirectResponse
    {
        if (! $this->authService->attempt(
            $request->validated('email'),
            $request->validated('password'),
            (bool) $request->boolean('remember'),
        )) {
            return back()->withErrors(['email' => __('customers::auth.invalid_credentials')])->onlyInput('email');
        }

        return redirect()->intended(route('storefront.account'));
    }

    public function showRegister(): View
    {
        return view('customers::storefront.register', $this->authPageData());
    }

    public function register(StorefrontRegisterRequest $request): RedirectResponse
    {
        $this->authService->register(new RegisterCustomerData(
            email: $request->validated('email'),
            name: $request->validated('name'),
            password: $request->validated('password'),
            phone: $request->validated('phone'),
        ));

        return redirect()->intended(route('storefront.account'));
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

    /**
     * @return array{storeName: string, logoUrl: ?string, supportEmail: ?string, supportPhone: ?string}
     */
    private function authPageData(): array
    {
        $storeName = config('app.name', 'Commerce Framework');
        $logoUrl = null;
        $supportEmail = null;
        $supportPhone = null;

        if (app()->bound(SettingQueryServiceInterface::class)) {
            $settings = app(SettingQueryServiceInterface::class);

            try {
                $name = $settings->get('store.name');
                if (is_string($name) && trim($name) !== '') {
                    $storeName = trim($name);
                }

                $supportEmail = $this->nullableString($settings->get('store.email'));
                $supportPhone = $this->nullableString($settings->get('store.phone'));
                $logoUuid = $this->nullableString($settings->get('store.logo_media_uuid'));
            } catch (\Throwable) {
                $logoUuid = null;
            }

            if (is_string($logoUuid) && $logoUuid !== '' && app()->bound(MediaQueryServiceInterface::class)) {
                try {
                    $media = app(MediaQueryServiceInterface::class);
                    $logoUrl = $media->getUrl($logoUuid, 'large')
                        ?? $media->getUrl($logoUuid, 'medium')
                        ?? $media->getUrl($logoUuid);
                } catch (\Throwable) {
                    $logoUrl = null;
                }
            }
        }

        return [
            'storeName' => is_string($storeName) && $storeName !== '' ? $storeName : 'Commerce Framework',
            'logoUrl' => $logoUrl,
            'supportEmail' => $supportEmail,
            'supportPhone' => $supportPhone,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
