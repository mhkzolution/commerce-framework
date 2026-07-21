<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CheckoutData extends DataTransferObject
{
    /**
     * @param  array<string, mixed>|null  $billingAddress
     * @param  array<string, mixed>|null  $shippingAddress
     */
    public function __construct(
        public ?string $customerEmail = null,
        public ?string $customerName = null,
        public ?string $customerUuid = null,
        public ?string $shippingAddressUuid = null,
        public ?string $billingAddressUuid = null,
        public ?array $billingAddress = null,
        public ?array $shippingAddress = null,
        public ?string $shippingMethodUuid = null,
    ) {}
}
