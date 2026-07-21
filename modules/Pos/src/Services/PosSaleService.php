<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\DTO\CartLineData;
use Commerce\Cart\Services\CartService;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Pricing\PriceResolverInterface;
use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Orders\Contracts\OrderServiceInterface;
use Commerce\Orders\DTO\CreateOrderData;
use Commerce\Orders\DTO\OrderLineData;
use Commerce\Orders\Models\Order;
use Commerce\Payment\Contracts\PaymentServiceInterface;
use Commerce\Pos\Models\Register;
use Commerce\Pos\Models\Session;
use Commerce\Pos\Support\PosCartStorageFactory;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

final class PosSaleService
{
    public function __construct(
        private readonly PosCartStorageFactory $cartStorageFactory,
        private readonly ProductQueryServiceInterface $productQueryService,
        private readonly InventoryQueryServiceInterface $inventoryQueryService,
        private readonly PriceResolverInterface $priceResolver,
        private readonly OrderServiceInterface $orderService,
    ) {}

    public function cart(Register $register): CartServiceInterface
    {
        return new CartService(
            $this->cartStorageFactory->make($register->uuid),
            $this->productQueryService,
            $this->inventoryQueryService,
            $this->priceResolver,
        );
    }

    /**
     * @return list<ProductVariant>
     */
    public function searchProducts(string $query, int $limit = 12): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        return ProductVariant::query()
            ->with(['product'])
            ->whereHas('product', static function ($inner): void {
                $inner->visibleOnStorefront();
            })
            ->where(function ($inner) use ($query): void {
                $inner->where('sku', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%")
                    ->orWhereHas('product', static function ($product) use ($query): void {
                        $product->where('name', 'like', "%{$query}%")
                            ->orWhere('slug', 'like', "%{$query}%");
                    });
            })
            ->orderBy('sku')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function findVariantBySku(string $sku): ?ProductVariant
    {
        return ProductVariant::query()
            ->with('product')
            ->where('sku', $sku)
            ->whereHas('product', static fn ($query) => $query->visibleOnStorefront())
            ->first();
    }

    public function completeSale(
        Register $register,
        ?Session $session = null,
        ?string $customerName = null,
        ?string $customerEmail = null,
    ): Order {
        $cartService = $this->cart($register);
        $cart = $cartService->get();

        if ($cart->lines === []) {
            throw new DomainException('Cart is empty.');
        }

        return DB::transaction(function () use ($cartService, $cart, $register, $session, $customerName, $customerEmail): Order {
            $orderLines = array_map(
                static fn ($line) => new OrderLineData(
                    purchasableUuid: $line->purchasableUuid,
                    quantity: $line->quantity,
                ),
                $cart->lines,
            );

            $order = $this->orderService->create(new CreateOrderData(
                lines: $orderLines,
                customerName: $customerName ?: 'Walk-in Customer',
                customerEmail: $customerEmail,
                channel: 'pos',
                currency: $cart->currency,
            ));

            if (app()->bound(PaymentServiceInterface::class)) {
                $payment = app(PaymentServiceInterface::class)->createForOrder(
                    $order->uuid,
                    $order->grand_total,
                    $order->currency,
                );
                app(PaymentServiceInterface::class)->markPaid($payment->uuid, 'POS-CASH-' . $register->code);
            } else {
                $order = $this->orderService->confirm($order->uuid);
            }

            $cartService->clear();

            return $order->fresh(['lineItems']);
        });
    }
}
