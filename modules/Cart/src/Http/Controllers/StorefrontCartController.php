<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Contracts\CheckoutServiceInterface;
use Commerce\Cart\DTO\CartLineData;
use Commerce\Cart\Http\Requests\AddCartLineRequest;
use Commerce\Cart\Http\Requests\CheckoutRequest;
use Commerce\Cart\Http\Requests\UpdateCartLineRequest;
use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Contracts\Payment\PaymentQueryServiceInterface;
use Commerce\Contracts\Shipping\ShippingQuoteServiceInterface;
use Commerce\Contracts\Tax\TaxQuoteServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Customers\Models\Customer;
use Commerce\Customers\Services\CustomerAddressQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class StorefrontCartController extends Controller
{
    public function __construct(
        private readonly CartServiceInterface $cartService,
        private readonly CheckoutServiceInterface $checkoutService,
        private readonly OrderQueryServiceInterface $orderQueryService,
        private readonly CustomerAddressQueryService $addressQueryService,
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

    public function update(UpdateCartLineRequest $request, string $purchasableUuid): RedirectResponse
    {
        try {
            $this->cartService->update($purchasableUuid, (int) $request->validated('quantity'));
        } catch (DomainException|EntityNotFoundException $exception) {
            return redirect()->route('storefront.cart.index')->withErrors(['cart' => $exception->getMessage()]);
        }

        return redirect()->route('storefront.cart.index')->with('status', 'Cart updated.');
    }

    public function destroy(string $purchasableUuid): RedirectResponse
    {
        $this->cartService->remove($purchasableUuid);

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

    public function checkoutForm(): View
    {
        /** @var Customer|null $customer */
        $customer = auth('customer')->user();

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
        ]);
    }

    public function checkout(CheckoutRequest $request): RedirectResponse
    {
        /** @var Customer|null $customer */
        $customer = auth('customer')->user();

        try {
            $order = $this->checkoutService->checkout($request->toCheckoutData(
                customerUuid: $customer?->uuid ?? $request->validated('customer_uuid'),
                customerEmail: $request->validated('customer_email') ?? $customer?->email,
                customerName: $request->validated('customer_name') ?? $customer?->name,
            ));
        } catch (DomainException|EntityNotFoundException $exception) {
            return redirect()->route('storefront.checkout')->withErrors(['checkout' => $exception->getMessage()]);
        }

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
}
