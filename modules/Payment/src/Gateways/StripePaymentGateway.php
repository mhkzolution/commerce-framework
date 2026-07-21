<?php

declare(strict_types=1);

namespace Commerce\Payment\Gateways;

use Commerce\Contracts\Payment\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;

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

        $response = Http::asForm()
            ->withToken($secretKey)
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => (int) $payment->amount,
                'currency' => strtolower((string) $payment->currency),
                'metadata[payment_uuid]' => (string) $payment->uuid,
                'description' => 'Order payment ' . ($payment->order_uuid ?? ''),
                'automatic_payment_methods[enabled]' => 'true',
            ])
            ->throw()
            ->json();

        return [
            'reference' => (string) ($response['id'] ?? ''),
            'client_secret' => (string) ($response['client_secret'] ?? ''),
            'redirect_url' => route('storefront.payment.show', $payment),
            'publishable_key' => config('payment.stripe.publishable_key'),
        ];
    }

    public function handleWebhook(array $payload, ?string $signature = null): ?string
    {
        if (($payload['type'] ?? '') === 'payment_intent.succeeded') {
            return $payload['data']['object']['metadata']['payment_uuid'] ?? null;
        }

        if (($payload['type'] ?? '') === 'payment_intent.payment_failed') {
            return $payload['data']['object']['metadata']['payment_uuid'] ?? null;
        }

        return null;
    }

    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        $secret = (string) config('payment.stripe.webhook_secret');

        if ($secret === '' || $signature === null) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signature) as $element) {
            [$key, $value] = array_pad(explode('=', $element, 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key] = $value;
            }
        }

        $timestamp = $parts['t'] ?? null;
        $expected = $parts['v1'] ?? null;

        if ($timestamp === null || $expected === null) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $computed = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($computed, $expected);
    }
}
