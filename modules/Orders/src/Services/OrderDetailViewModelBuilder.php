<?php

declare(strict_types=1);

namespace Commerce\Orders\Services;

use Commerce\Iam\Contracts\User\UserServiceInterface;
use Commerce\Inventory\Models\StockMovement;
use Commerce\Orders\Contracts\OrderFulfillmentServiceInterface;
use Commerce\Orders\Models\Order;
use Commerce\Orders\Support\AddressFormatter;
use Commerce\Orders\Support\OrderFinancialStatus;
use Commerce\Orders\Support\OrderFulfillmentStatus;
use Commerce\Orders\ViewModels\OrderDetailView;
use Commerce\Payment\Models\Payment;
use Illuminate\Support\Collection;

final class OrderDetailViewModelBuilder
{
    public function __construct(
        private readonly OrderFulfillmentServiceInterface $fulfillment,
    ) {}

    public function build(Order $order): OrderDetailView
    {
        $order->loadMissing(['lineItems', 'events', 'shipments.items']);

        $payments = $this->payments($order);
        $shippedByLineId = $this->fulfillment->shippedQuantityByLineId($order);
        $fulfillmentStatus = OrderFulfillmentStatus::fromOrder($order, $shippedByLineId);

        return new OrderDetailView(
            order: $order,
            financialStatus: OrderFinancialStatus::fromPayments((int) $order->grand_total, $payments),
            fulfillmentStatus: $fulfillmentStatus,
            payments: $payments,
            stockMovements: $this->stockMovements($order),
            timeline: $order->events->sortByDesc('id')->values(),
            shipments: $order->shipments->sortByDesc('id')->values(),
            shippedByLineId: $shippedByLineId,
            shippingLines: AddressFormatter::lines($order->shipping_address),
            billingLines: AddressFormatter::lines($order->billing_address),
            createdBy: $this->user($order->created_by_user_uuid),
            updatedBy: $this->user($order->updated_by_user_uuid),
            canConfirm: $order->isPending(),
            canComplete: $order->isConfirmed(),
            canCancel: $order->isPending() || $order->isConfirmed(),
            canFulfill: ($order->isConfirmed() || $order->isCompleted()) && $fulfillmentStatus !== OrderFulfillmentStatus::FULFILLED,
            canEditNotes: ! $order->isCancelled(),
        );
    }

    /**
     * @return Collection<int, mixed>
     */
    private function payments(Order $order): Collection
    {
        if (! class_exists(Payment::class)) {
            return collect();
        }

        return Payment::query()
            ->where('order_uuid', $order->uuid)
            ->latest()
            ->get();
    }

    /**
     * @return Collection<int, mixed>
     */
    private function stockMovements(Order $order): Collection
    {
        if (! class_exists(StockMovement::class)) {
            return collect();
        }

        return StockMovement::query()
            ->with('inventoryItem')
            ->where('reference_type', Order::REFERENCE_TYPE)
            ->where('reference_id', $order->uuid)
            ->latest()
            ->get();
    }

    private function user(?string $uuid): ?object
    {
        if (! is_string($uuid) || $uuid === '' || ! app()->bound(UserServiceInterface::class)) {
            return null;
        }

        return app(UserServiceInterface::class)->findByUuid($uuid);
    }
}
