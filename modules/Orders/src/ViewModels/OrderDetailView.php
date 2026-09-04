<?php

declare(strict_types=1);

namespace Commerce\Orders\ViewModels;

use Commerce\Orders\Models\Order;
use Illuminate\Support\Collection;

final readonly class OrderDetailView
{
    /**
     * @param  Collection<int, mixed>  $payments
     * @param  Collection<int, mixed>  $stockMovements
     * @param  Collection<int, mixed>  $timeline
     * @param  Collection<int, mixed>  $shipments
     * @param  array<int, int>  $shippedByLineId
     * @param  list<string>  $shippingLines
     * @param  list<string>  $billingLines
     */
    public function __construct(
        public Order $order,
        public string $financialStatus,
        public string $fulfillmentStatus,
        public Collection $payments,
        public Collection $stockMovements,
        public Collection $timeline,
        public Collection $shipments,
        public array $shippedByLineId,
        public array $shippingLines,
        public array $billingLines,
        public ?object $createdBy,
        public ?object $updatedBy,
        public bool $canConfirm,
        public bool $canComplete,
        public bool $canCancel,
        public bool $canFulfill,
        public bool $canEditNotes,
    ) {}

    public function remainingForLine(int $lineId, int $ordered): int
    {
        return max(0, $ordered - ($this->shippedByLineId[$lineId] ?? 0));
    }

    public function shippedForLine(int $lineId): int
    {
        return $this->shippedByLineId[$lineId] ?? 0;
    }
}
