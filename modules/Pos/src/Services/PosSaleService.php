<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Cart\DTO\CartData;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Pricing\PriceResolverInterface;
use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Contracts\Tax\TaxQuoteServiceInterface;
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

    public function cart(Register $register): PosCartService
    {
        return new PosCartService(
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
            ->with(['product.media'])
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
            ->with(['product.media'])
            ->where('sku', $sku)
            ->whereHas('product', static fn ($query) => $query->visibleOnStorefront())
            ->first();
    }

    /**
     * @param  list<array{method: string, amount_minor: int}>  $mixedPayments
     */
    public function completeSale(
        Register $register,
        ?Session $session = null,
        ?string $customerName = null,
        ?string $customerEmail = null,
        ?string $customerUuid = null,
        string $paymentMethod = 'cash',
        ?string $notes = null,
        array $mixedPayments = [],
        ?int $amountReceived = null,
    ): Order {
        $cartService = $this->cart($register);
        $cart = $cartService->get();

        if ($cart->lines === []) {
            throw new DomainException('Cart is empty.');
        }

        $taxable = max(0, $cart->subtotal - $cart->discountTotal);
        $taxTotal = 0;

        if (app()->bound(TaxQuoteServiceInterface::class) && $taxable > 0) {
            $quote = app(TaxQuoteServiceInterface::class)->calculate($taxable, null, $cart->currency);
            $taxTotal = (int) ($quote->total ?? 0);
        }

        $grandTotal = max(0, $taxable + $taxTotal);
        $payments = $this->normalizePayments($mixedPayments, $paymentMethod, $grandTotal);
        $this->assertPaymentsCoverTotal($payments, $grandTotal);

        $rawLines = $cartService->rawLines();
        $cashReceived = $amountReceived;
        $change = null;

        if ($cashReceived !== null) {
            $cashTotal = array_sum(array_map(
                static fn (array $payment): int => $payment['method'] === 'cash' ? $payment['amount_minor'] : 0,
                $payments,
            ));
            if ($cashTotal > 0) {
                $change = max(0, $cashReceived - $cashTotal);
            }
        }

        return DB::transaction(function () use (
            $cartService,
            $cart,
            $register,
            $session,
            $customerName,
            $customerEmail,
            $customerUuid,
            $paymentMethod,
            $notes,
            $taxTotal,
            $payments,
            $rawLines,
            $cashReceived,
            $change,
        ): Order {
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
                customerUuid: $customerUuid,
                channel: 'pos',
                currency: $cart->currency,
                taxTotal: $taxTotal,
                discountTotal: $cart->discountTotal,
                promotionCode: $cart->couponCode,
            ));

            $this->applyLineOverrides($order, $rawLines, $cart);

            $paymentReference = count($payments) > 1
                ? 'POS-MIXED-'.$register->code
                : 'POS-'.strtoupper($payments[0]['method']).'-'.$register->code;

            if (app()->bound(PaymentServiceInterface::class)) {
                $payment = app(PaymentServiceInterface::class)->createForOrder(
                    $order->uuid,
                    $order->grand_total,
                    $order->currency,
                );
                app(PaymentServiceInterface::class)->markPaid($payment->uuid, $paymentReference);
            } else {
                $order = $this->orderService->confirm($order->uuid);
            }

            if ($session !== null) {
                $cashSaleTotal = array_sum(array_map(
                    static fn (array $payment): int => $payment['method'] === 'cash' ? $payment['amount_minor'] : 0,
                    $payments,
                ));

                $order->update([
                    'meta' => array_merge($order->meta ?? [], [
                        'pos_session_uuid' => $session->uuid,
                        'pos_register_uuid' => $register->uuid,
                        'pos_register_code' => $register->code,
                        'pos_payment_method' => $paymentMethod,
                        'pos_payments' => $payments,
                        'pos_notes' => $notes,
                        'pos_cashier' => auth()->user()?->name,
                        'pos_cash_received' => $cashReceived,
                        'pos_change_amount' => $change,
                    ]),
                ]);

                if ($cashSaleTotal > 0) {
                    app(PosSessionService::class)->recordCashSale($session, $cashSaleTotal);
                }
            }

            $cartService->clear();

            return $order->fresh(['lineItems']);
        });
    }

    /**
     * @param  list<array{purchasable_uuid: string, quantity: int, unit_price_override?: int, line_discount_minor?: int}>  $rawLines
     */
    private function applyLineOverrides(Order $order, array $rawLines, CartData $cart): void
    {
        $rawByUuid = [];
        foreach ($rawLines as $line) {
            $rawByUuid[$line['purchasable_uuid']] = $line;
        }

        $subtotal = 0;
        foreach ($order->lineItems as $lineItem) {
            $raw = $rawByUuid[$lineItem->purchasable_uuid] ?? null;
            $resolved = collect($cart->lines)->first(
                static fn ($line) => $line->purchasableUuid === $lineItem->purchasable_uuid,
            );

            if ($resolved === null) {
                $subtotal += $lineItem->line_total;

                continue;
            }

            $lineItem->update([
                'unit_price' => $resolved->unitPrice,
                'line_total' => $resolved->lineTotal,
            ]);
            $subtotal += $resolved->lineTotal;
        }

        $grandTotal = max(0, $subtotal - $order->discount_total + $order->tax_total + $order->shipping_total);
        $order->update([
            'subtotal' => $subtotal,
            'grand_total' => $grandTotal,
        ]);
    }

    /**
     * @param  list<array{method: string, amount_minor: int}>  $mixedPayments
     * @return list<array{method: string, amount_minor: int}>
     */
    private function normalizePayments(array $mixedPayments, string $fallbackMethod, int $grandTotal): array
    {
        if ($mixedPayments === []) {
            return [['method' => $fallbackMethod, 'amount_minor' => $grandTotal]];
        }

        return array_values(array_map(
            static fn (array $payment): array => [
                'method' => (string) ($payment['method'] ?? $fallbackMethod),
                'amount_minor' => max(0, (int) ($payment['amount_minor'] ?? 0)),
            ],
            $mixedPayments,
        ));
    }

    /**
     * @param  list<array{method: string, amount_minor: int}>  $payments
     */
    private function assertPaymentsCoverTotal(array $payments, int $grandTotal): void
    {
        $total = array_sum(array_column($payments, 'amount_minor'));

        if ($total !== $grandTotal) {
            throw new DomainException('Payment amounts must equal the grand total.');
        }
    }
}
