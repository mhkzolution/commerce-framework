<?php

declare(strict_types=1);

namespace Commerce\Orders\Services;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Orders\Contracts\OrderFulfillmentServiceInterface;
use Commerce\Orders\DTO\CreateShipmentData;
use Commerce\Orders\Models\Order;
use Commerce\Orders\Models\OrderEvent;
use Commerce\Orders\Models\OrderLineItem;
use Commerce\Orders\Models\OrderShipment;
use Commerce\Orders\Models\OrderShipmentItem;
use Illuminate\Support\Facades\DB;

final class OrderFulfillmentService implements OrderFulfillmentServiceInterface
{
    public function __construct(
        private readonly OrderEventRecorder $events,
    ) {}

    public function createShipment(Order $order, CreateShipmentData $data): OrderShipment
    {
        if ($order->isCancelled() || $order->isPending()) {
            throw new DomainException(
                $order->isPending()
                    ? 'Confirm the order before creating a shipment.'
                    : 'This order cannot be fulfilled.',
            );
        }

        $order->loadMissing(['lineItems', 'shipments.items']);
        $shipped = $this->shippedQuantityByLineId($order);
        $lines = $order->lineItems->keyBy('uuid');
        $items = [];

        foreach ($data->quantitiesByLineUuid as $lineUuid => $quantity) {
            $qty = (int) $quantity;
            if ($qty <= 0) {
                continue;
            }

            $line = $lines->get($lineUuid);
            if (! $line instanceof OrderLineItem) {
                throw new DomainException('Quantity exceeds remaining unfulfilled items.');
            }

            $remaining = max(0, (int) $line->quantity - ($shipped[(int) $line->id] ?? 0));
            if ($qty > $remaining) {
                throw new DomainException('Quantity exceeds remaining unfulfilled items.');
            }

            $items[] = ['line' => $line, 'quantity' => $qty];
        }

        if ($items === []) {
            throw new DomainException('Quantity exceeds remaining unfulfilled items.');
        }

        return DB::transaction(function () use ($order, $data, $items): OrderShipment {
            $shipment = OrderShipment::query()->create([
                'order_id' => $order->id,
                'status' => OrderShipment::STATUS_SHIPPED,
                'carrier' => $this->blankToNull($data->carrier),
                'tracking_number' => $this->blankToNull($data->trackingNumber),
                'tracking_url' => $this->blankToNull($data->trackingUrl),
                'notes' => $this->blankToNull($data->notes),
                'created_by_user_uuid' => $data->createdByUserUuid,
                'shipped_at' => now(),
            ]);

            foreach ($items as $item) {
                OrderShipmentItem::query()->create([
                    'shipment_id' => $shipment->id,
                    'order_line_item_id' => $item['line']->id,
                    'quantity' => $item['quantity'],
                ]);
            }

            $order->update(['updated_by_user_uuid' => $data->createdByUserUuid ?? $order->updated_by_user_uuid]);

            $this->events->record(
                $order,
                OrderEvent::TYPE_SHIPMENT_CREATED,
                'Shipment created',
                $data->createdByUserUuid,
                [
                    'shipment_uuid' => $shipment->uuid,
                    'tracking_number' => $shipment->tracking_number,
                    'carrier' => $shipment->carrier,
                ],
            );

            return $shipment->load('items');
        });
    }

    public function updateTracking(
        Order $order,
        string $shipmentUuid,
        ?string $carrier,
        ?string $trackingNumber,
        ?string $actorUserUuid = null,
    ): OrderShipment {
        $shipment = $this->findShipment($order, $shipmentUuid);

        if ($shipment->isCancelled()) {
            throw new DomainException('This shipment cannot be updated.');
        }

        $shipment->update([
            'carrier' => $this->blankToNull($carrier),
            'tracking_number' => $this->blankToNull($trackingNumber),
        ]);

        $order->update(['updated_by_user_uuid' => $actorUserUuid ?? $order->updated_by_user_uuid]);

        $this->events->record(
            $order,
            OrderEvent::TYPE_SHIPMENT_TRACKING_UPDATED,
            'Tracking updated',
            $actorUserUuid,
            [
                'shipment_uuid' => $shipment->uuid,
                'tracking_number' => $shipment->tracking_number,
                'carrier' => $shipment->carrier,
            ],
        );

        return $shipment->refresh();
    }

    public function cancelShipment(Order $order, string $shipmentUuid, ?string $actorUserUuid = null): OrderShipment
    {
        $shipment = $this->findShipment($order, $shipmentUuid);

        if ($shipment->isCancelled()) {
            throw new DomainException('This shipment cannot be updated.');
        }

        $shipment->update([
            'status' => OrderShipment::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        $order->update(['updated_by_user_uuid' => $actorUserUuid ?? $order->updated_by_user_uuid]);

        $this->events->record(
            $order,
            OrderEvent::TYPE_SHIPMENT_CANCELLED,
            'Shipment cancelled',
            $actorUserUuid,
            ['shipment_uuid' => $shipment->uuid],
        );

        return $shipment->refresh();
    }

    /**
     * @return array<int, int>
     */
    public function shippedQuantityByLineId(Order $order): array
    {
        $order->loadMissing('shipments.items');
        $shipped = [];

        foreach ($order->shipments as $shipment) {
            if ($shipment->isCancelled()) {
                continue;
            }

            foreach ($shipment->items as $item) {
                $lineId = (int) $item->order_line_item_id;
                $shipped[$lineId] = ($shipped[$lineId] ?? 0) + (int) $item->quantity;
            }
        }

        return $shipped;
    }

    private function findShipment(Order $order, string $shipmentUuid): OrderShipment
    {
        $shipment = OrderShipment::query()
            ->where('order_id', $order->id)
            ->where('uuid', $shipmentUuid)
            ->first();

        if ($shipment === null) {
            throw new EntityNotFoundException("Shipment [{$shipmentUuid}] not found.");
        }

        return $shipment;
    }

    private function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
