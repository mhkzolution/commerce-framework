<?php

declare(strict_types=1);

namespace Commerce\Payment\Gateways;

use Commerce\Contracts\Payment\PaymentGatewayInterface;

final class SimulatedPaymentGateway implements PaymentGatewayInterface
{
    public function getCode(): string
    {
        return 'simulated';
    }

    public function getName(): string
    {
        return 'Simulated Gateway';
    }

    public function isEnabled(): bool
    {
        return (bool) config('payment.simulate_gateway', true);
    }

    public function initiate(object $payment, array $context = []): array
    {
        return [
            'reference' => 'SIM-' . strtoupper(substr((string) $payment->uuid, 0, 8)),
        ];
    }

    public function handleWebhook(array $payload, ?string $signature = null): ?string
    {
        return isset($payload['payment_uuid']) ? (string) $payload['payment_uuid'] : null;
    }
}
