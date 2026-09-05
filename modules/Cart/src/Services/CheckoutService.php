<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Contracts\CheckoutServiceInterface;
use Commerce\Cart\DTO\CartData;
use Commerce\Cart\DTO\CheckoutData;
use Commerce\Contracts\Customer\CustomerQueryServiceInterface;
use Commerce\Contracts\Promotion\PromotionServiceInterface;
use Commerce\Contracts\Shipping\ShippingQuoteServiceInterface;
use Commerce\Contracts\Tax\TaxQuoteServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Customers\Models\Customer;
use Commerce\Customers\Models\CustomerAddress;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Orders\Contracts\OrderServiceInterface;
use Commerce\Orders\DTO\CreateOrderData;
use Commerce\Orders\DTO\OrderLineData;
use Commerce\Orders\Models\Order;
use Commerce\Payment\Contracts\PaymentServiceInterface;
use Illuminate\Support\Facades\DB;

final class CheckoutService extends BaseService implements CheckoutServiceInterface
{
    public function __construct(
        private readonly CartServiceInterface $cartService,
        private readonly OrderServiceInterface $orderService,
        private readonly InventoryServiceInterface $inventoryService,
    ) {}

    public function checkout(CheckoutData $data): Order
    {
        $cart = $this->cartService->get();

        if ($cart->lines === []) {
            throw new DomainException('Cart is empty.');
        }

        foreach ($cart->lines as $line) {
            if (! $line->isPurchasable) {
                throw new DomainException("{$line->name} is no longer available.");
            }

            if ($line->available < $line->quantity) {
                throw new DomainException("Insufficient stock for {$line->name}.");
            }
        }

        $orderLines = array_map(
            static fn ($line) => new OrderLineData(
                purchasableUuid: $line->purchasableUuid,
                quantity: $line->quantity,
            ),
            $cart->lines,
        );

        return DB::transaction(function () use ($data, $cart, $orderLines): Order {
            $customerUuid = $data->customerUuid;
            $customerEmail = $data->customerEmail;
            $customerName = $data->customerName;

            if ($customerUuid === null && $customerEmail !== null && app()->bound(CustomerQueryServiceInterface::class)) {
                $customer = app(CustomerQueryServiceInterface::class)->findByEmail($customerEmail);
                if ($customer !== null) {
                    $customerUuid = $customer->uuid;
                    $customerName = $customerName ?: $customer->name;
                }
            }

            $shippingAddress = $this->resolveAddress($data->shippingAddressUuid, $data->customerUuid)
                ?? $data->shippingAddress;
            $billingAddress = $this->resolveAddress($data->billingAddressUuid, $data->customerUuid)
                ?? $data->billingAddress;
            $shipping = $this->resolveShipping($data, $cart, $shippingAddress);
            $promotion = $this->resolvePromotion($cart);
            $tax = $this->resolveTax($cart, $shippingAddress);

            $order = $this->orderService->create(new CreateOrderData(
                lines: $orderLines,
                customerEmail: $customerEmail,
                customerName: $customerName,
                customerUuid: $customerUuid,
                currency: $cart->currency,
                channel: 'web',
                billingAddress: $billingAddress,
                shippingAddress: $shippingAddress,
                shippingMethodUuid: $shipping['uuid'],
                shippingTotal: $shipping['total'],
                shippingMethodName: $shipping['name'],
                discountTotal: $promotion['discount'],
                promotionUuid: $promotion['uuid'],
                promotionCode: $promotion['code'],
                taxTotal: $tax,
            ));

            if ($promotion['uuid'] !== null && app()->bound(PromotionServiceInterface::class)) {
                app(PromotionServiceInterface::class)->redeem($promotion['uuid']);
            }

            if (config('cart.auto_confirm_on_checkout', false)) {
                $paymentConfirmsOrder = app()->bound(PaymentServiceInterface::class)
                    && (bool) config('payment.confirm_order_on_payment', true);

                if (! $paymentConfirmsOrder) {
                    $order = $this->orderService->confirm($order->uuid);
                }
            }

            if (app()->bound(PaymentServiceInterface::class)) {
                app(PaymentServiceInterface::class)->createForOrder(
                    $order->uuid,
                    $order->grand_total,
                    $order->currency,
                );
            }

            if (config('inventory.reserve_on_checkout', true)) {
                foreach ($order->lineItems as $line) {
                    $this->inventoryService->reserve(
                        purchasableUuid: $line->purchasable_uuid,
                        quantity: $line->quantity,
                        referenceType: Order::REFERENCE_TYPE,
                        referenceId: $order->uuid,
                        reason: "Checkout {$order->order_number}",
                    );
                }
            }

            $this->cartService->clear();

            return $order->fresh(['lineItems']);
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveAddress(?string $uuid, ?string $customerUuid): ?array
    {
        if ($uuid === null) {
            return null;
        }

        $address = CustomerAddress::query()->where('uuid', $uuid)->first();

        if ($address === null) {
            throw new EntityNotFoundException("Address [{$uuid}] not found.");
        }

        if ($customerUuid === null) {
            throw new EntityNotFoundException("Address [{$uuid}] not found.");
        }

        $customer = Customer::query()->where('uuid', $customerUuid)->first();

        if ($customer === null || $address->customer_id !== $customer->id) {
            throw new EntityNotFoundException("Address [{$uuid}] not found.");
        }

        return $address->toOrderArray() + [
            'recipient_name' => $customer->name,
            'phone' => $customer->phone,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $shippingAddress
     * @return array{uuid: ?string, total: int, name: ?string}
     */
    private function resolveShipping(CheckoutData $data, CartData $cart, ?array $shippingAddress): array
    {
        if (! app()->bound(ShippingQuoteServiceInterface::class)) {
            return ['uuid' => null, 'total' => 0, 'name' => null];
        }

        if ($data->shippingMethodUuid === null) {
            throw new DomainException('Please select a shipping method.');
        }

        $countryCode = isset($shippingAddress['country_code'])
            ? strtoupper((string) $shippingAddress['country_code'])
            : null;

        $quote = app(ShippingQuoteServiceInterface::class)->resolveQuote(
            $data->shippingMethodUuid,
            $cart->taxableSubtotal(),
            $countryCode,
            $cart->currency,
        );

        if ($quote === null) {
            throw new DomainException('Selected shipping method is not available.');
        }

        return [
            'uuid' => $quote->uuid,
            'total' => $quote->price,
            'name' => $quote->name,
        ];
    }

    /** @return array{uuid: ?string, code: ?string, discount: int} */
    private function resolvePromotion(CartData $cart): array
    {
        if ($cart->couponCode === null || ! app()->bound(PromotionServiceInterface::class)) {
            return ['uuid' => null, 'code' => null, 'discount' => 0];
        }

        $quote = app(PromotionServiceInterface::class)->resolve($cart->couponCode, $cart->subtotal);

        if ($quote === null) {
            throw new DomainException('Promotion code is no longer valid.');
        }

        return [
            'uuid' => $quote->uuid,
            'code' => $quote->code,
            'discount' => $quote->discount,
        ];
    }

    /** @param  array<string, mixed>|null  $shippingAddress */
    private function resolveTax(CartData $cart, ?array $shippingAddress): int
    {
        if (! app()->bound(TaxQuoteServiceInterface::class)) {
            return 0;
        }

        $countryCode = isset($shippingAddress['country_code'])
            ? strtoupper((string) $shippingAddress['country_code'])
            : null;

        return app(TaxQuoteServiceInterface::class)
            ->calculate($cart->taxableSubtotal(), $countryCode, $cart->currency)
            ->total;
    }
}
