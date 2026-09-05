<?php

declare(strict_types=1);

namespace Commerce\Orders\Support;

use Commerce\Orders\Models\OrderShipment;

final class StorefrontOrderJourney
{
    /**
     * @return list<array{key: string, label: string, state: 'complete'|'current'|'upcoming'}>
     */
    public static function timeline(object $order): array
    {
        $currentIndex = self::currentIndex($order);
        $steps = ['created', 'confirmed', 'processing', 'shipped', 'completed'];

        $timeline = [];

        foreach ($steps as $index => $key) {
            $state = 'upcoming';
            if ($index < $currentIndex) {
                $state = 'complete';
            } elseif ($index === $currentIndex) {
                $state = 'current';
            }

            $timeline[] = [
                'key' => $key,
                'label' => __('storefront::storefront.order_step_'.$key),
                'state' => $state,
            ];
        }

        return $timeline;
    }

    private static function currentIndex(object $order): int
    {
        $status = (string) ($order->status ?? '');
        $hasShipment = self::hasActiveShipment($order);

        if ($status === 'completed') {
            return 4;
        }

        if ($hasShipment) {
            return 3;
        }

        if ($status === 'confirmed') {
            return 2;
        }

        return 0;
    }

    private static function hasActiveShipment(object $order): bool
    {
        if (! isset($order->shipments)) {
            return false;
        }

        foreach ($order->shipments as $shipment) {
            if (! $shipment instanceof OrderShipment) {
                continue;
            }

            if ($shipment->isCancelled()) {
                continue;
            }

            if ($shipment->status === OrderShipment::STATUS_SHIPPED || $shipment->tracking_number || $shipment->shipped_at) {
                return true;
            }
        }

        return false;
    }
}
