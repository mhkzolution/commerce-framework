<?php

declare(strict_types=1);

namespace Commerce\Payment\Gateways;

use Commerce\Contracts\Payment\PaymentGatewayInterface;

final class StripePaymentGateway implements PaymentGatewayInterface
{
    public function getCode(): string
    {
        return 'stripe';
    }

    public function getName(): string
    {
        return 'Stripe';
    }

    public function isEnabled(): bool
    {
        return config('payment.stripe.secret_key') !== null
            && config('payment.stripe.secret_key') !== '';
    }

    public function initiate(object $payment, array $context = []): array
    {
        $secretKey = (string) config('payment.stripe.secret_key');

        if ($secretKey === '') {
            throw new \RuntimeException('Stripe is not configured.');
        }

        $reference = 'STRIPE-' . strtoupper(substr((string) $payment->uuid, 0, 8));

        return [
            'reference' => $reference,
            'client_secret' => $reference,
            'redirect_url' => route('storefront.payment.show', $payment),
        ];
    }

    public function handleWebhook(array $payload, ?string $signature = null): ?string
    {
        if (($payload['type'] ?? '') === 'payment_intent.succeeded') {
            return $payload['data']['object']['metadata']['payment_uuid'] ?? null;
        }

        return null;
    }
}
