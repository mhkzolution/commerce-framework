<?php

declare(strict_types=1);

namespace Commerce\Orders\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CreateOrderData extends DataTransferObject
{
    /**
     * @param  list<OrderLineData>  $lines
     * @param  array<string, mixed>|null  $billingAddress
     * @param  array<string, mixed>|null  $shippingAddress
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        public array $lines,
        public ?string $customerEmail = null,
        public ?string $customerName = null,
        public ?string $customerUuid = null,
        public ?string $currency = null,
        public ?string $channel = null,
        public ?array $billingAddress = null,
        public ?array $shippingAddress = null,
        public ?string $shippingMethodUuid = null,
        public ?int $shippingTotal = null,
        public ?string $shippingMethodName = null,
        public int $discountTotal = 0,
        public ?string $promotionUuid = null,
        public ?string $promotionCode = null,
        public int $taxTotal = 0,
        public ?array $meta = null,
        public ?string $idempotencyKey = null,
        public ?string $createdByUserUuid = null,
        public bool $requirePurchasable = true,
    ) {}
}
