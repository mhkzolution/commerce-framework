<?php

declare(strict_types=1);

namespace Commerce\Orders\Services;

use Commerce\Contracts\Customer\CustomerQueryServiceInterface;
use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Order\OrderStatus;
use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Contracts\Purchasable\PurchasableInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Orders\Contracts\OrderServiceInterface;
use Commerce\Orders\DTO\CreateOrderData;
use Commerce\Orders\DTO\OrderLineData;
use Commerce\Orders\Events\OrderCancelled;
use Commerce\Orders\Events\OrderCompleted;
use Commerce\Orders\Events\OrderConfirmed;
use Commerce\Orders\Events\OrderCreated;
use Commerce\Orders\Models\Order;
use Commerce\Orders\Models\OrderEvent;
use Commerce\Orders\Models\OrderLineItem;
use Commerce\Orders\Support\OrderNumberGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class OrderService extends BaseService implements OrderServiceInterface
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
        private readonly ProductQueryServiceInterface $productQueryService,
        private readonly InventoryQueryServiceInterface $inventoryQueryService,
        private readonly InventoryServiceInterface $inventoryService,
        private readonly OrderEventRecorder $events,
    ) {}

    public function create(CreateOrderData $data): Order
    {
        if ($data->lines === []) {
            throw new DomainException('An order must have at least one line item.');
        }

        if (is_string($data->idempotencyKey) && $data->idempotencyKey !== '') {
            $existing = Order::query()->where('idempotency_key', $data->idempotencyKey)->first();
            if ($existing !== null) {
                return $existing->load('lineItems');
            }
        }

        try {
            return DB::transaction(function () use ($data): Order {
                $resolvedLines = $this->resolveLines($data->lines, $data->requirePurchasable);
                $customer = $this->resolveCustomerSnapshot($data);
                $subtotal = array_sum(array_column($resolvedLines, 'line_total'));
                $discountTotal = max(0, $data->discountTotal);
                $taxTotal = max(0, $data->taxTotal);
                $shippingTotal = $data->shippingTotal ?? 0;

                $order = Order::query()->create([
                    'order_number' => OrderNumberGenerator::next(),
                    'status' => OrderStatus::Pending->value,
                    'currency' => $data->currency ?? config('orders.default_currency', 'USD'),
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'promotion_uuid' => $data->promotionUuid,
                    'promotion_code' => $data->promotionCode,
                    'tax_total' => $taxTotal,
                    'shipping_total' => $shippingTotal,
                    'grand_total' => max(0, $subtotal - $discountTotal + $taxTotal + $shippingTotal),
                    'customer_uuid' => $customer['uuid'],
                    'customer_email' => $customer['email'],
                    'customer_name' => $customer['name'],
                    'billing_address' => $data->billingAddress,
                    'shipping_address' => $data->shippingAddress,
                    'shipping_method_uuid' => $data->shippingMethodUuid,
                    'shipping_method_name' => $data->shippingMethodName,
                    'channel' => $data->channel ?? config('orders.default_channel', 'web'),
                    'created_by_user_uuid' => $data->createdByUserUuid,
                    'updated_by_user_uuid' => $data->createdByUserUuid,
                    'idempotency_key' => $data->idempotencyKey,
                    'meta' => $data->meta,
                ]);

                foreach ($resolvedLines as $line) {
                    OrderLineItem::query()->create([
                        'order_id' => $order->id,
                        'purchasable_uuid' => $line['purchasable_uuid'],
                        'sku' => $line['sku'],
                        'name' => $line['name'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'line_total' => $line['line_total'],
                        'meta' => $line['meta'],
                    ]);
                }

                $order->load('lineItems');

                $this->eventBus->dispatchReliable(new OrderCreated(
                    orderUuid: $order->uuid,
                    orderNumber: $order->order_number,
                    tenantId: $order->tenant_id,
                ));

                $this->events->record(
                    $order,
                    OrderEvent::TYPE_CREATED,
                    'Order created',
                    $data->createdByUserUuid,
                );

                return $order;
            });
        } catch (QueryException $exception) {
            if (is_string($data->idempotencyKey) && $data->idempotencyKey !== '') {
                $existing = Order::query()->where('idempotency_key', $data->idempotencyKey)->first();
                if ($existing !== null) {
                    return $existing->load('lineItems');
                }
            }

            throw $exception;
        }
    }

    public function confirm(string $uuid, ?string $actorUserUuid = null): Order
    {
        return DB::transaction(function () use ($uuid, $actorUserUuid): Order {
            $order = $this->findOrFail($uuid);

            if (! $order->isPending()) {
                throw new DomainException('Only pending orders can be confirmed.');
            }

            $order->loadMissing('lineItems');

            foreach ($order->lineItems as $line) {
                if (! $this->inventoryQueryService->isAvailable($line->purchasable_uuid, $line->quantity)) {
                    throw new DomainException("Insufficient stock for {$line->name}.");
                }
            }

            foreach ($order->lineItems as $line) {
                $this->inventoryService->sale(
                    purchasableUuid: $line->purchasable_uuid,
                    quantity: $line->quantity,
                    referenceType: Order::REFERENCE_TYPE,
                    referenceId: $order->uuid,
                    reason: "Order {$order->order_number}",
                );

                if (config('inventory.reserve_on_checkout', true)) {
                    $level = $this->inventoryQueryService->getStockLevel($line->purchasable_uuid);
                    if ($level->getReserved() >= $line->quantity) {
                        $this->inventoryService->release(
                            purchasableUuid: $line->purchasable_uuid,
                            quantity: $line->quantity,
                            referenceType: Order::REFERENCE_TYPE,
                            referenceId: $order->uuid,
                            reason: "Confirm {$order->order_number}",
                        );
                    }
                }
            }

            $order->update([
                'status' => OrderStatus::Confirmed->value,
                'confirmed_at' => now(),
                'updated_by_user_uuid' => $actorUserUuid ?? $order->updated_by_user_uuid,
            ]);

            $order = $order->fresh(['lineItems']);

            $this->eventBus->dispatch(new OrderConfirmed(
                orderUuid: $order->uuid,
                orderNumber: $order->order_number,
                tenantId: $order->tenant_id,
            ));

            $this->events->record($order, OrderEvent::TYPE_CONFIRMED, 'Order confirmed', $actorUserUuid);

            return $order;
        });
    }

    public function complete(string $uuid, ?string $actorUserUuid = null): Order
    {
        return DB::transaction(function () use ($uuid, $actorUserUuid): Order {
            $order = $this->findOrFail($uuid);

            if (! $order->isConfirmed()) {
                throw new DomainException('Only confirmed orders can be completed.');
            }

            $order->update([
                'status' => OrderStatus::Completed->value,
                'completed_at' => now(),
                'updated_by_user_uuid' => $actorUserUuid ?? $order->updated_by_user_uuid,
            ]);

            $order = $order->fresh(['lineItems']);

            $this->eventBus->dispatch(new OrderCompleted(
                orderUuid: $order->uuid,
                orderNumber: $order->order_number,
                tenantId: $order->tenant_id,
            ));

            $this->events->record($order, OrderEvent::TYPE_COMPLETED, 'Order completed', $actorUserUuid);

            return $order;
        });
    }

    public function cancel(string $uuid, ?string $actorUserUuid = null): Order
    {
        return DB::transaction(function () use ($uuid, $actorUserUuid): Order {
            $order = $this->findOrFail($uuid);

            if ($order->isCompleted() || $order->isCancelled()) {
                throw new DomainException('This order cannot be cancelled.');
            }

            $wasConfirmed = $order->isConfirmed();
            $order->loadMissing('lineItems');

            if ($wasConfirmed) {
                foreach ($order->lineItems as $line) {
                    $this->inventoryService->returnStock(
                        purchasableUuid: $line->purchasable_uuid,
                        quantity: $line->quantity,
                        referenceType: Order::REFERENCE_TYPE,
                        referenceId: $order->uuid,
                        reason: "Cancelled order {$order->order_number}",
                    );
                }
            } elseif (config('inventory.reserve_on_checkout', true)) {
                foreach ($order->lineItems as $line) {
                    $level = $this->inventoryQueryService->getStockLevel($line->purchasable_uuid);
                    if ($level->getReserved() >= $line->quantity) {
                        $this->inventoryService->release(
                            purchasableUuid: $line->purchasable_uuid,
                            quantity: $line->quantity,
                            referenceType: Order::REFERENCE_TYPE,
                            referenceId: $order->uuid,
                            reason: "Cancelled pending order {$order->order_number}",
                        );
                    }
                }
            }

            $order->update([
                'status' => OrderStatus::Cancelled->value,
                'cancelled_at' => now(),
                'updated_by_user_uuid' => $actorUserUuid ?? $order->updated_by_user_uuid,
            ]);

            $order = $order->fresh(['lineItems']);

            $this->eventBus->dispatch(new OrderCancelled(
                orderUuid: $order->uuid,
                orderNumber: $order->order_number,
                tenantId: $order->tenant_id,
            ));

            $this->events->record($order, OrderEvent::TYPE_CANCELLED, 'Order cancelled', $actorUserUuid);

            return $order;
        });
    }

    /**
     * @param  list<OrderLineData>  $lines
     * @return list<array{purchasable_uuid: string, sku: ?string, name: string, quantity: int, unit_price: int, line_total: int, meta: array<string, mixed>}>
     */
    private function resolveLines(array $lines, bool $requirePurchasable = true): array
    {
        $resolved = [];

        foreach ($lines as $line) {
            if ($line->quantity <= 0) {
                throw new DomainException('Line item quantity must be greater than zero.');
            }

            $variant = $this->productQueryService->findVariantByUuid($line->purchasableUuid);

            if ($variant === null) {
                throw new EntityNotFoundException("Purchasable variant [{$line->purchasableUuid}] not found.");
            }

            if ($requirePurchasable && $variant instanceof PurchasableInterface && ! $variant->isPurchasable()) {
                throw new DomainException("Variant [{$line->purchasableUuid}] is not available for purchase.");
            }

            $catalogPrice = (int) $variant->price;
            $unitPrice = $line->unitPrice ?? $catalogPrice;
            $productName = $variant->product?->name ?? $variant->name ?? 'Product';
            $variantName = $variant->name;
            $name = (is_string($variantName) && $variantName !== '' && $variantName !== $productName)
                ? $productName.' — '.$variantName
                : $productName;

            $resolved[] = [
                'purchasable_uuid' => $line->purchasableUuid,
                'sku' => $variant->sku,
                'name' => $name,
                'quantity' => $line->quantity,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $line->quantity,
                'meta' => [
                    'product_name' => $productName,
                    'variant_name' => $variantName,
                    'catalog_unit_price' => $catalogPrice,
                    'price_overridden' => $line->unitPrice !== null && $unitPrice !== $catalogPrice,
                ],
            ];
        }

        return $resolved;
    }

    /**
     * @return array{uuid: ?string, email: ?string, name: ?string}
     */
    private function resolveCustomerSnapshot(CreateOrderData $data): array
    {
        $uuid = $data->customerUuid;
        $email = $data->customerEmail;
        $name = $data->customerName;

        if ($uuid !== null && app()->bound(CustomerQueryServiceInterface::class)) {
            $customer = app(CustomerQueryServiceInterface::class)->findByUuid($uuid);

            if ($customer === null) {
                throw new EntityNotFoundException("Customer [{$uuid}] not found.");
            }

            if (isset($customer->status) && $customer->status !== 'active') {
                throw new DomainException('Customer is not active.');
            }

            $email = $email ?: $customer->email;
            $name = $name ?: $customer->name;
        }

        return [
            'uuid' => $uuid,
            'email' => $email,
            'name' => $name,
        ];
    }

    private function findOrFail(string $uuid): Order
    {
        $order = Order::query()->where('uuid', $uuid)->first();

        if ($order === null) {
            throw new EntityNotFoundException("Order [{$uuid}] not found.");
        }

        return $order;
    }
}
