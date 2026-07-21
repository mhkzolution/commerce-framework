<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use Commerce\Orders\Models\Order;
use Commerce\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StripeWebhookTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCheckoutDependencies();
    }

    public function test_stripe_webhook_marks_payment_as_paid(): void
    {
        config([
            'payment.stripe.webhook_secret' => 'whsec_test_secret',
        ]);

        $variant = $this->createPurchasableProduct(price: 2500, stock: 10);

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);

        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());

        $payment = Payment::query()->first();
        $this->assertNotNull($payment);
        $this->assertSame('pending', $payment->status);

        $payload = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'metadata' => [
                        'payment_uuid' => $payment->uuid,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, 'whsec_test_secret');

        $this->call(
            'POST',
            route('storefront.payment.webhook', ['gateway' => 'stripe']),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            ],
            $payload,
        )->assertOk()->assertJson(['received' => true]);

        $payment->refresh();
        $order = Order::query()->where('uuid', $payment->order_uuid)->first();

        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($order);
        $this->assertSame('confirmed', $order->status);
    }

    public function test_stripe_webhook_rejects_invalid_signature(): void
    {
        config([
            'payment.stripe.webhook_secret' => 'whsec_test_secret',
        ]);

        $payload = json_encode(['type' => 'payment_intent.succeeded'], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            route('storefront.payment.webhook', ['gateway' => 'stripe']),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 't=123,v1=invalid',
            ],
            $payload,
        )->assertStatus(400);
    }
}
