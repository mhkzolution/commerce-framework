<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Contracts\CheckoutServiceInterface;
use Commerce\Cart\DTO\CartLineData;
use Commerce\Cart\DTO\CheckoutData;
use Commerce\Cart\Http\Requests\AddCartLineRequest;
use Commerce\Cart\Http\Requests\CheckoutRequest;
use Commerce\Cart\Http\Requests\UpdateCartLineRequest;
use Commerce\Cart\Http\Resources\CartResource;
use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Contracts\Payment\PaymentQueryServiceInterface;
use Commerce\Contracts\Shipping\ShippingQuoteServiceInterface;
use Commerce\Contracts\Tax\TaxQuoteServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Customers\Contracts\CustomerAddressServiceInterface;
use Commerce\Customers\DTO\CreateAddressData;
use Commerce\Customers\Models\Customer;
use Commerce\Customers\Services\CustomerAddressQueryService;
use Commerce\Customers\Support\StorefrontAuthRedirect;
use Commerce\Payment\Services\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class StorefrontCartController extends Controller
{
    public function __construct(
        private readonly CartServiceInterface $cartService,
        private readonly CheckoutServiceInterface $checkoutService,
        private readonly OrderQueryServiceInterface $orderQueryService,
        private readonly CustomerAddressQueryService $addressQueryService,
        private readonly CustomerAddressServiceInterface $addressService,
    ) {}

    public function index(): View
    {
        return view('cart::storefront.cart', [
            'cart' => $this->cartService->get(),
        ]);
    }

    public function store(AddCartLineRequest $request): RedirectResponse
    {
        try {
            $this->cartService->add(new CartLineData(
                purchasableUuid: $request->validated('purchasable_uuid'),
                quantity: (int) $request->validated('quantity'),
            ));
        } catch (DomainException|EntityNotFoundException $exception) {
            return redirect()->back()->withErrors(['cart' => $exception->getMessage()]);
        }

        if ($request->validated('redirect_to') === 'checkout') {
            return redirect()->route('storefront.checkout')->with('status', 'Item added to cart.');
        }

        return redirect()->route('storefront.cart.index')->with('status', 'Item added to cart.');
    }

    public function update(UpdateCartLineRequest $request, string $purchasableUuid): RedirectResponse|JsonResponse
    {
        try {
            $this->cartService->update($purchasableUuid, (int) $request->validated('quantity'));
        } catch (DomainException|EntityNotFoundException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return redirect()->route('storefront.cart.index')->withErrors(['cart' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'data' => (new CartResource($this->cartService->get()))->resolve($request),
            ]);
        }

        return redirect()->route('storefront.cart.index')->with('status', 'Cart updated.');
    }

    public function destroy(Request $request, string $purchasableUuid): RedirectResponse|JsonResponse
    {
        $this->cartService->remove($purchasableUuid);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => (new CartResource($this->cartService->get()))->resolve($request),
            ]);
        }

        return redirect()->route('storefront.cart.index')->with('status', 'Item removed.');
    }

    public function clear(): RedirectResponse
    {
        $this->cartService->clear();

        return redirect()->route('storefront.cart.index')->with('status', 'Cart cleared.');
    }

    public function applyCoupon(): RedirectResponse
    {
        $code = (string) request()->string('code');

        try {
            $this->cartService->applyCoupon($code);
        } catch (DomainException $exception) {
            return redirect()->route('storefront.cart.index')->withErrors(['coupon' => $exception->getMessage()]);
        }

        return redirect()->route('storefront.cart.index')->with('status', 'Promotion applied.');
    }

    public function removeCoupon(): RedirectResponse
    {
        $this->cartService->removeCoupon();

        return redirect()->route('storefront.cart.index')->with('status', 'Promotion removed.');
    }

    public function setCurrency(): RedirectResponse
    {
        $currency = (string) request()->string('currency');

        try {
            $this->cartService->setCurrency($currency);
        } catch (DomainException $exception) {
            return redirect()->back()->withErrors(['currency' => $exception->getMessage()]);
        }

        return redirect()->back()->with('status', 'Currency updated.');
    }

    public function checkoutForm(Request $request): View
    {
        /** @var Customer|null $customer */
        $customer = auth('customer')->user();

        $this->restoreCheckoutDraft($request);

        $cart = $this->cartService->get();
        $taxQuote = app()->bound(TaxQuoteServiceInterface::class)
            ? app(TaxQuoteServiceInterface::class)->calculate($cart->taxableSubtotal(), null, $cart->currency)
            : (object) ['total' => 0, 'lines' => []];

        return view('cart::storefront.checkout', [
            'cart' => $cart,
            'customer' => $customer,
            'addresses' => $customer ? $this->addressQueryService->forCustomer($customer->uuid) : collect(),
            'shippingQuotes' => app()->bound(ShippingQuoteServiceInterface::class)
                ? app(ShippingQuoteServiceInterface::class)->availableQuotes($cart->taxableSubtotal(), null, $cart->currency)
                : [],
            'taxTotal' => $taxQuote->total,
            'paymentMethods' => $this->storefrontPaymentMethods(),
        ]);
    }

    public function saveDraft(Request $request): RedirectResponse|JsonResponse
    {
        $request->session()->put(
            'checkout.draft',
            $request->except(['_token', 'password', 'password_confirmation', 'next']),
        );
        StorefrontAuthRedirect::rememberUrl(route('storefront.checkout'), $request);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('storefront.checkout');
    }

    public function checkout(CheckoutRequest $request): RedirectResponse
    {
        /** @var Customer|null $customer */
        $customer = auth('customer')->user();

        try {
            $data = $request->toCheckoutData(
                customerUuid: $customer?->uuid ?? $request->validated('customer_uuid'),
                customerEmail: $request->validated('customer_email') ?? $customer?->email,
                customerName: $request->validated('customer_name') ?? $customer?->name,
            );

            $order = $this->checkoutService->checkout($data);

            if ($customer !== null) {
                $this->persistNewCheckoutAddresses($customer, $request, $data);
            }
        } catch (DomainException|EntityNotFoundException $exception) {
            return redirect()->route('storefront.checkout')->withErrors(['checkout' => $exception->getMessage()]);
        }

        $request->session()->forget('checkout.draft');

        if (app()->bound(PaymentQueryServiceInterface::class)) {
            $payment = app(PaymentQueryServiceInterface::class)->findPendingByOrderUuid($order->uuid);
            if ($payment !== null) {
                return redirect()->route('storefront.payment.show', $payment);
            }
        }

        return redirect()->route('storefront.checkout.confirmation', $order);
    }

    public function confirmation(string $order): View
    {
        $model = $this->orderQueryService->findByUuid($order);
        abort_if($model === null, 404);

        return view('cart::storefront.confirmation', [
            'order' => $model,
        ]);
    }

    private function restoreCheckoutDraft(Request $request): void
    {
        $draft = $request->session()->get('checkout.draft');

        if (! is_array($draft) || $request->session()->has('_old_input')) {
            return;
        }

        $request->session()->now('_old_input', $draft);
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    private function storefrontPaymentMethods(): array
    {
        if (! app()->bound(PaymentGatewayManager::class)) {
            return [
                ['code' => 'card', 'name' => __('storefront::storefront.payment_method_card')],
            ];
        }

        $labels = [
            'stripe' => __('storefront::storefront.payment_method_card'),
            'simulated' => __('storefront::storefront.payment_method_secure'),
        ];

        $methods = array_values(array_map(
            static fn (object $gateway): array => [
                'code' => $gateway->getCode(),
                'name' => $labels[$gateway->getCode()] ?? $gateway->getName(),
            ],
            app(PaymentGatewayManager::class)->enabled(),
        ));

        if (! in_array('stripe', array_column($methods, 'code'), true)) {
            array_unshift($methods, [
                'code' => 'card',
                'name' => __('storefront::storefront.payment_method_card'),
            ]);
        }

        return $methods;
    }

    private function persistNewCheckoutAddresses(
        Customer $customer,
        CheckoutRequest $request,
        CheckoutData $data,
    ): void {
        if ($request->boolean('save_shipping_address')
            && $data->shippingAddressUuid === null
            && is_array($data->shippingAddress)
        ) {
            $this->createAddressFromCheckout(
                $customer,
                $data->shippingAddress,
                'shipping',
                $request->validated('shipping_address_label'),
            );
        }

        $updateShippingUuid = $request->validated('update_shipping_address_uuid');
        if (is_string($updateShippingUuid) && $updateShippingUuid !== '' && is_array($data->shippingAddress)) {
            $this->updateAddressFromCheckout($customer, $updateShippingUuid, $data->shippingAddress, 'shipping', $request->validated('shipping_address_label'));
        }

        if ($request->boolean('save_billing_address')
            && ! $request->boolean('billing_same_as_shipping')
            && $data->billingAddressUuid === null
            && is_array($data->billingAddress)
        ) {
            $this->createAddressFromCheckout(
                $customer,
                $data->billingAddress,
                'billing',
                $request->validated('billing_address_label'),
            );
        }

        $updateBillingUuid = $request->validated('update_billing_address_uuid');
        if (is_string($updateBillingUuid)
            && $updateBillingUuid !== ''
            && ! $request->boolean('billing_same_as_shipping')
            && is_array($data->billingAddress)
        ) {
            $this->updateAddressFromCheckout($customer, $updateBillingUuid, $data->billingAddress, 'billing', $request->validated('billing_address_label'));
        }
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function createAddressFromCheckout(
        Customer $customer,
        array $address,
        string $type,
        ?string $label,
    ): void {
        $line1 = trim((string) ($address['line1'] ?? ''));
        $city = trim((string) ($address['city'] ?? ''));
        $postalCode = trim((string) ($address['postal_code'] ?? ''));
        $countryCode = strtoupper(trim((string) ($address['country_code'] ?? '')));

        if ($line1 === '' || $city === '' || $postalCode === '' || strlen($countryCode) !== 2) {
            return;
        }

        $this->addressService->create(new CreateAddressData(
            customerUuid: $customer->uuid,
            line1: $line1,
            city: $city,
            postalCode: $postalCode,
            countryCode: $countryCode,
            type: $type,
            label: is_string($label) && $label !== '' ? $label : null,
            line2: isset($address['line2']) ? (string) $address['line2'] : null,
            state: isset($address['state']) ? (string) $address['state'] : null,
            district: isset($address['district']) ? (string) $address['district'] : null,
            subdistrict: isset($address['subdistrict']) ? (string) $address['subdistrict'] : null,
        ));
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function updateAddressFromCheckout(
        Customer $customer,
        string $uuid,
        array $address,
        string $type,
        ?string $label,
    ): void {
        $line1 = trim((string) ($address['line1'] ?? ''));
        $city = trim((string) ($address['city'] ?? ''));
        $postalCode = trim((string) ($address['postal_code'] ?? ''));
        $countryCode = strtoupper(trim((string) ($address['country_code'] ?? '')));

        if ($line1 === '' || $city === '' || $postalCode === '' || strlen($countryCode) !== 2) {
            return;
        }

        $this->addressService->update($uuid, new CreateAddressData(
            customerUuid: $customer->uuid,
            line1: $line1,
            city: $city,
            postalCode: $postalCode,
            countryCode: $countryCode,
            type: $type,
            label: is_string($label) && $label !== '' ? $label : null,
            line2: isset($address['line2']) ? (string) $address['line2'] : null,
            state: isset($address['state']) ? (string) $address['state'] : null,
            district: isset($address['district']) ? (string) $address['district'] : null,
            subdistrict: isset($address['subdistrict']) ? (string) $address['subdistrict'] : null,
        ));
    }
}
