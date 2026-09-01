<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Controllers\Storefront;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Services\StorefrontShopQueryService;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Customers\Contracts\CustomerAddressServiceInterface;
use Commerce\Customers\Contracts\CustomerAuthServiceInterface;
use Commerce\Customers\Contracts\CustomerServiceInterface;
use Commerce\Customers\DTO\CreateAddressData;
use Commerce\Customers\DTO\RegisterCustomerData;
use Commerce\Customers\DTO\UpdateCustomerData;
use Commerce\Customers\Http\Requests\StoreAddressRequest;
use Commerce\Customers\Http\Requests\StorefrontForgotPasswordRequest;
use Commerce\Customers\Http\Requests\StorefrontLoginRequest;
use Commerce\Customers\Http\Requests\StorefrontRegisterRequest;
use Commerce\Customers\Http\Requests\StorefrontResetPasswordRequest;
use Commerce\Customers\Http\Requests\StorefrontSendOtpRequest;
use Commerce\Customers\Http\Requests\UpdateProfileRequest;
use Commerce\Customers\Models\Customer;
use Commerce\Customers\Services\CustomerAddressQueryService;
use Commerce\Customers\Services\CustomerOtpService;
use Commerce\Customers\Services\CustomerPasswordResetService;
use Commerce\Customers\Services\StorefrontAuthConfigService;
use Commerce\Product\Models\Product;
use Commerce\Wishlist\Services\StorefrontWishlistPresenter;
use Commerce\Wishlist\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function show(
        StorefrontShopQueryService $shopQueryService,
        CartServiceInterface $cartService,
    ): View {
        $customer = $this->authService->current();
        abort_if($customer === null, 403);

        $newProducts = Product::query()
            ->with(['variants', 'media', 'categories'])
            ->visibleOnStorefront()
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $cart = $cartService->get();
        $converter = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)
            : null;

        $productPaginator = new LengthAwarePaginator(
            $newProducts,
            $newProducts->count(),
            max(1, $newProducts->count()),
            1,
        );

        return view('customers::storefront.account.dashboard', [
            'customer' => $customer,
            'newProducts' => $newProducts,
            'stockLevels' => $shopQueryService->stockLevelsForProducts($productPaginator),
            'displayCurrency' => $cart->currency,
            'baseCurrency' => $converter?->baseCurrency() ?? $cart->currency,
            'currencyConverter' => $converter,
        ]);
    }

    public function orders(): View
    {
        $customer = $this->authService->current();
        abort_if($customer === null, 403);

        $orders = app()->bound(OrderQueryServiceInterface::class)
            ? $this->orderQueryService->paginateForCustomer($customer->uuid, 10)
            : null;

        return view('customers::storefront.account.orders', [
            'customer' => $customer,
            'orders' => $orders,
            'orderStatuses' => config('orders.statuses', []),
        ]);
    }

    public function wishlist(): View
    {
        $customer = $this->authService->current();
        abort_if($customer === null, 403);

        $items = [];

        if (app()->bound(WishlistService::class)) {
            $wishlistService = app(WishlistService::class);
            $presenter = app(StorefrontWishlistPresenter::class);
            $items = $presenter->presentItems($wishlistService->itemsForCustomer($customer));
        }

        return view('customers::storefront.account.wishlist', [
            'customer' => $customer,
            'wishlistItems' => $items,
        ]);
    }

    public function shipping(): View
    {
        $customer = $this->authService->current();
        abort_if($customer === null, 403);

        return view('customers::storefront.account.shipping', [
            'customer' => $customer,
            'addresses' => $this->addressQueryService->forCustomer($customer->uuid),
        ]);
    }

    public function profile(): View
    {
        $customer = $this->authService->current();
        abort_if($customer === null, 403);

        return view('customers::storefront.account.profile', [
            'customer' => $customer,
        ]);
    }

    public function showLogin(StorefrontAuthConfigService $authConfig): View
    {
        return view('customers::storefront.login', [
            'authConfig' => $authConfig,
            'loginMode' => old('login_mode', 'email'),
        ]);
    }

    public function login(StorefrontLoginRequest $request): RedirectResponse
    {
        $mode = $request->input('login_mode', 'email');

        if ($mode === 'otp') {
            if (! config('customers.storefront.otp.enabled', false)) {
                return back()->withErrors(['identifier' => __('customers::auth.otp_unavailable')])->onlyInput('identifier', 'login_mode');
            }

            $identifier = (string) $request->validated('identifier');
            $code = (string) $request->validated('otp');

            if (! app(CustomerOtpService::class)->verify($identifier, $code)) {
                return back()
                    ->withErrors(['otp' => __('customers::auth.otp_invalid')])
                    ->onlyInput('identifier', 'login_mode');
            }

            $customer = app(CustomerOtpService::class)->findCustomer($identifier);

            if ($customer === null) {
                return back()
                    ->withErrors(['identifier' => __('customers::auth.otp_account_not_found')])
                    ->onlyInput('identifier', 'login_mode');
            }

            Auth::guard('customer')->login($customer, (bool) $request->boolean('remember'));

            return redirect()->intended(route('storefront.account'));
        }

        $remember = (bool) $request->boolean('remember');

        $authenticated = $mode === 'phone'
            ? $this->authService->attemptByPhone((string) $request->validated('phone'), (string) $request->validated('password'), $remember)
            : $this->authService->attempt((string) $request->validated('email'), (string) $request->validated('password'), $remember);

        if (! $authenticated) {
            return back()
                ->withErrors(['email' => __('customers::auth.invalid_credentials')])
                ->onlyInput('email', 'phone', 'login_mode');
        }

        return redirect()->intended(route('storefront.account'));
    }

    public function showForgotPassword(StorefrontAuthConfigService $authConfig): View|RedirectResponse
    {
        if (! $authConfig->forgotPasswordEnabled()) {
            return redirect()->route('storefront.account.login');
        }

        return view('customers::storefront.forgot-password', [
            'authConfig' => $authConfig,
        ]);
    }

    public function forgotPassword(StorefrontForgotPasswordRequest $request, CustomerPasswordResetService $passwordReset): RedirectResponse
    {
        $passwordReset->sendResetLink($request->validated('email'));

        return back()->with('status', __('customers::auth.forgot_password_sent'));
    }

    public function showResetPassword(string $token): View
    {
        return view('customers::storefront.reset-password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    public function resetPassword(StorefrontResetPasswordRequest $request, CustomerPasswordResetService $passwordReset): RedirectResponse
    {
        if (! $passwordReset->reset(
            $request->validated('email'),
            $request->validated('token'),
            $request->validated('password'),
        )) {
            return back()
                ->withErrors(['email' => __('customers::auth.reset_password_invalid')])
                ->onlyInput('email');
        }

        return redirect()
            ->route('storefront.account.login')
            ->with('status', __('customers::auth.reset_password_success'));
    }

    public function sendOtp(StorefrontSendOtpRequest $request, CustomerOtpService $otpService): RedirectResponse|JsonResponse
    {
        if (! config('customers.storefront.otp.enabled', false)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => __('customers::auth.otp_unavailable'),
                ], 422);
            }

            return back()->withErrors(['identifier' => __('customers::auth.otp_unavailable')]);
        }

        $otpService->send($request->validated('identifier'));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => __('customers::auth.otp_sent'),
            ]);
        }

        return back()
            ->with('status', __('customers::auth.otp_sent'))
            ->withInput(['identifier' => $request->validated('identifier'), 'login_mode' => 'otp']);
    }

    public function showRegister(StorefrontAuthConfigService $authConfig): View|RedirectResponse
    {
        if (! config('customers.storefront.registration.enabled', true)) {
            return redirect()->route('storefront.account.login');
        }

        return view('customers::storefront.register', [
            'authConfig' => $authConfig,
        ]);
    }

    public function register(StorefrontRegisterRequest $request): RedirectResponse
    {
        if (! config('customers.storefront.registration.enabled', true)) {
            abort(403);
        }
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
