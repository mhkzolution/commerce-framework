<?php

declare(strict_types=1);

namespace Commerce\Contracts\Payment;

interface PaymentGatewayInterface
{
    public function getCode(): string;

    public function getName(): string;

    public function isEnabled(): bool;

    /**
     * @return array{redirect_url?: string, client_secret?: string, reference?: string}
     */
    public function initiate(object $payment, array $context = []): array;

    public function handleWebhook(array $payload, ?string $signature = null): ?string;

    /**
     * @return array{reference?: string}
     */
    public function refund(object $payment, ?int $amount = null): array;
}
