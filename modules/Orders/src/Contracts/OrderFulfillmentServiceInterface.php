<?php

declare(strict_types=1);

namespace Commerce\Orders\Contracts;

use Commerce\Orders\DTO\CreateShipmentData;
use Commerce\Orders\Models\Order;
use Commerce\Orders\Models\OrderShipment;

interface OrderFulfillmentServiceInterface
{
    public function createShipment(Order $order, CreateShipmentData $data): OrderShipment;

    public function updateTracking(
        Order $order,
        string $shipmentUuid,
        ?string $carrier,
        ?string $trackingNumber,
        ?string $actorUserUuid = null,
    ): OrderShipment;

    public function cancelShipment(Order $order, string $shipmentUuid, ?string $actorUserUuid = null): OrderShipment;

    /**
     * @return array<int, int>
     */
    public function shippedQuantityByLineId(Order $order): array;
}
