<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Contracts\CheckoutServiceInterface;
use Commerce\Cart\DTO\CartLineData;
use Commerce\Cart\Http\Requests\AddCartLineRequest;
use Commerce\Cart\Http\Requests\CheckoutRequest;
use Commerce\Cart\Http\Requests\DestroyCartItemsRequest;
use Commerce\Cart\Http\Requests\PrepareCartCheckoutRequest;
use Commerce\Cart\Http\Requests\UpdateCartLineRequest;
use Commerce\Cart\Services\StorefrontCartPageService;
use Commerce\Cart\Support\CartCheckoutSelection;
use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Contracts\Payment\PaymentQueryServiceInterface;
use Commerce\Contracts\Tax\TaxQuoteServiceInterface;
use Commerce\Core\Channel\ChannelContext;
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
        private readonly StorefrontCartPageService $cartPageService,
        private readonly ChannelContext $channelContext,
    ) {}

    public function index(): View
    {
        CartCheckoutSelection::clear();
        $cart = $this->cartService->get();

        return view('cart::storefront.cart', [
            'cart' => $cart,
            'page' => app(StorefrontCartPageService::class)->forCart($cart),
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

        return redirect()->route(
            $request->validated('redirect_to') === 'checkout' ? 'storefront.checkout' : 'storefront.cart.index',
        )->with('status', 'Item added to cart.');
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

    public function destroyMany(DestroyCartItemsRequest $request): RedirectResponse
    {
        foreach ($request->validated('items') as $purchasableUuid) {
            $this->cartService->remove((string) $purchasableUuid);
        }

        return redirect()->route('storefront.cart.index')->with('status', __('storefront::storefront.items_removed'));
    }

    public function prepareCheckout(PrepareCartCheckoutRequest $request): RedirectResponse
    {
        $items = array_map(static fn (mixed $uuid): string => (string) $uuid, $request->validated('items'));
        $cart = $this->cartService->get();
        $allowed = array_flip(array_map(static fn ($line) => $line->purchasableUuid, $cart->lines));

        foreach ($items as $purchasableUuid) {
            if (! isset($allowed[$purchasableUuid])) {
                return redirect()->route('storefront.cart.index')->withErrors([
                    'cart' => __('storefront::storefront.checkout_selection_invalid'),
                ]);
            }
        }

        CartCheckoutSelection::set($items);

        return redirect()->route('storefront.checkout');
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

        return redirect()->back()->with('status', __('storefront::storefront.currency_updated'));
    }

    public function setLocale(): RedirectResponse
    {
        $locale = (string) request()->string('locale');
        $available = array_keys(config('admin.locale.available', []));

        if (! in_array($locale, $available, true)) {
            return redirect()->back()->withErrors(['locale' => 'Unsupported locale.']);
        }

        session()->put((string) config('admin.locale.session_key', 'commerce.locale'), $locale);
        app()->setLocale($locale);
        $this->channelContext->setLocale($locale);

        return redirect()->back()->with('status', __('storefront::storefront.locale_updated'));
    }

    public function checkoutForm(): View|RedirectResponse
    {
        /** @var Customer|null $customer */
        $customer = auth('customer')->user();

        $cart = CartCheckoutSelection::filterCart($this->cartService->get());

        if ($cart->lines === []) {
            return redirect()->route('storefront.cart.index')->withErrors([
                'cart' => __('storefront::storefront.checkout_selection_empty'),
            ]);
        }

        $taxQuote = app()->bound(TaxQuoteServiceInterface::class)
            ? app(TaxQuoteServiceInterface::class)->calculate($cart->taxableSubtotal(), null, $cart->currency)
            : (object) ['total' => 0, 'lines' => []];

        $page = $this->cartPageService->forCart($cart);
        $addresses = $customer ? $this->addressQueryService->forCustomer($customer->uuid) : collect();
        $shippingAddresses = $addresses->filter(
            fn ($address) => in_array($address->type, ['shipping', 'both'], true),
        );
        $billingAddresses = $addresses->filter(
            fn ($address) => in_array($address->type, ['billing', 'both'], true),
        );

        return view('cart::storefront.checkout', [
            'cart' => $cart,
            'lines' => $page->lines,
            'customer' => $customer,
            'addresses' => $addresses,
            'shippingQuotes' => $page->shippingQuotes,
            'taxTotal' => $taxQuote->total,
            'estimatedTotal' => max(0, $cart->taxableSubtotal() + $taxQuote->total + $page->cheapestShipping),
            'cheapestShipping' => $page->cheapestShipping,
            'estimatedDelivery' => $page->estimatedDelivery,
            'showManualShipping' => ! $customer || $shippingAddresses->isEmpty(),
            'showManualBilling' => ! $customer || $billingAddresses->isEmpty(),
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
