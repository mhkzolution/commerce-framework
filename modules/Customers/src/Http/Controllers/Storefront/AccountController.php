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
use Commerce\Customers\Http\Requests\ChangePasswordRequest;
use Commerce\Customers\Http\Requests\DestroyAccountWishlistItemRequest;
use Commerce\Customers\Http\Requests\StoreAddressRequest;
use Commerce\Customers\Http\Requests\StorefrontLoginRequest;
use Commerce\Customers\Http\Requests\StorefrontRegisterRequest;
use Commerce\Customers\Http\Requests\UpdateProfileRequest;
use Commerce\Customers\Models\Customer;
use Commerce\Customers\Services\CustomerAddressQueryService;
use Commerce\Customers\Support\StorefrontAuthRedirect;
use Commerce\Wishlist\DTO\WishlistItemReferenceData;
use Commerce\Wishlist\Services\StorefrontWishlistPresenter;
use Commerce\Wishlist\Services\WishlistService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        $customer = $this->requireCustomer();
        $orders = $this->ordersForCustomer($customer, 5);
        $addresses = $this->addressQueryService->forCustomer($customer->uuid);
        $wishlistItems = $this->wishlistItems($customer);

        return view('customers::storefront.account.dashboard', [
            'customer' => $customer,
            'orders' => $orders,
            'orderStatuses' => config('orders.statuses', []),
            'addressCount' => $addresses->count(),
            'wishlistCount' => count($wishlistItems),
            'orderCount' => $orders?->total() ?? 0,
        ]);
    }

    public function orders(): View
    {
        $customer = $this->requireCustomer();

        return view('customers::storefront.account.orders', [
            'customer' => $customer,
            'orders' => $this->ordersForCustomer($customer, 15),
            'orderStatuses' => config('orders.statuses', []),
        ]);
    }

    public function showOrder(string $order): View
    {
        $customer = $this->requireCustomer();

        $model = $this->orderQueryService->findByUuid($order);
        abort_if($model === null || $model->customer_uuid !== $customer->uuid, 404);

        return view('customers::storefront.account.order', [
            'customer' => $customer,
            'order' => $model,
            'orderStatuses' => config('orders.statuses', []),
        ]);
    }

    public function addresses(): View
    {
        $customer = $this->requireCustomer();

        return view('customers::storefront.account.addresses', [
            'customer' => $customer,
            'addresses' => $this->addressQueryService->forCustomer($customer->uuid),
        ]);
    }

    public function storeAddress(StoreAddressRequest $request): RedirectResponse
    {
        $customer = $this->requireCustomer();

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

        return redirect()
            ->route('storefront.account.addresses')
            ->with('status', __('storefront::storefront.address_added'));
    }

    public function destroyAddress(string $address): RedirectResponse
    {
        $customer = $this->requireCustomer();

        $model = $this->addressQueryService->findByUuid($address);
        abort_if($model === null || $model->customer_id !== $customer->id, 404);

        $this->addressService->delete($address);

        return redirect()
            ->route('storefront.account.addresses')
            ->with('status', __('storefront::storefront.address_removed'));
    }

    public function wishlist(): View
    {
        $customer = $this->requireCustomer();

        return view('customers::storefront.account.wishlist', [
            'customer' => $customer,
            'items' => $this->wishlistItems($customer),
        ]);
    }

    public function destroyWishlistItem(DestroyAccountWishlistItemRequest $request): RedirectResponse
    {
        $customer = $this->requireCustomer();
        abort_unless(app()->bound(WishlistService::class), 404);

        $reference = WishlistItemReferenceData::fromArray($request->validated());
        abort_if($reference === null, 404);

        app(WishlistService::class)->removeItem($customer, $reference);

        return redirect()
            ->route('storefront.account.wishlist')
            ->with('status', __('storefront::storefront.wishlist_item_removed'));
    }

    public function profile(): View
    {
        return view('customers::storefront.account.profile', [
            'customer' => $this->requireCustomer(),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $customer = $this->requireCustomer();

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

        return redirect()
            ->route('storefront.account.profile')
            ->with('status', __('storefront::storefront.profile_updated'));
    }

    public function security(): View
    {
        return view('customers::storefront.account.security', [
            'customer' => $this->requireCustomer(),
        ]);
    }

    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $customer = $this->requireCustomer();
        $this->authService->changePassword($customer, $request->validated('password'));

        $updated = $customer->fresh();
        if ($updated instanceof Customer) {
            Auth::guard('customer')->setUser($updated);
        }

        return redirect()
            ->route('storefront.account.security')
            ->with('status', __('storefront::storefront.password_updated'));
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

        return StorefrontAuthRedirect::toIntended();
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

        return StorefrontAuthRedirect::toIntended();
    }

    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('storefront.shop.index');
    }

    private function requireCustomer(): Customer
    {
        $customer = $this->authService->current();
        abort_if($customer === null, 403);

        return $customer;
    }

    /**
     * @return LengthAwarePaginator<int, object>|null
     */
    private function ordersForCustomer(Customer $customer, int $perPage): ?LengthAwarePaginator
    {
        if (! app()->bound(OrderQueryServiceInterface::class)) {
            return null;
        }

        return $this->orderQueryService->paginateForCustomer($customer->uuid, $perPage);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function wishlistItems(Customer $customer): array
    {
        if (! app()->bound(WishlistService::class) || ! app()->bound(StorefrontWishlistPresenter::class)) {
            return [];
        }

        return app(StorefrontWishlistPresenter::class)
            ->presentItems(app(WishlistService::class)->itemsForCustomer($customer));
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
