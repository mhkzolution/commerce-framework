<?php

declare(strict_types=1);

namespace Commerce\Payment\Events;

use Commerce\Contracts\Event\DomainEventInterface;

final readonly class PaymentPaid implements DomainEventInterface
{
    public function __construct(
        public string $paymentUuid,
        public string $orderUuid,
        public int $amount,
        public string $currency,
        public ?string $gatewayReference = null,
        public ?int $tenantId = null,
    ) {}

    public function getEventName(): string
    {
        return 'payment.paid';
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function toPayload(): array
    {
        return [
            'payment_uuid' => $this->paymentUuid,
            'order_uuid' => $this->orderUuid,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'gateway_reference' => $this->gatewayReference,
            'tenant_id' => $this->tenantId,
        ];
    }
}
