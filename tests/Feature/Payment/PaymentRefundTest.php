<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Orders\Models\Order;
use Commerce\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class PaymentRefundTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seedCheckoutDependencies();
        config([
            'payment.gateway' => 'simulated',
            'payment.simulate_gateway' => true,
        ]);
    }

    public function test_admin_can_refund_paid_payment(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 5, sku: 'REFUND-SKU');

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);

        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());

        $order = Order::query()->first();
        $payment = Payment::query()->where('order_uuid', $order->uuid)->first();
        $this->assertNotNull($payment);

        $this->post(route('storefront.payment.pay', $payment));
        $this->assertSame('paid', $payment->fresh()->status);

        $this->actingAs(User::query()->first())
            ->post(route('admin.payments.refund', $payment))
            ->assertRedirect(route('admin.payments.show', $payment));

        $payment->refresh();
        $this->assertSame('refunded', $payment->status);
        $this->assertNotNull($payment->refunded_at);
        $this->assertNotNull($payment->refund_reference);
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_api_can_refund_with_manage_permission(): void
    {
        $variant = $this->createPurchasableProduct(price: 30, stock: 5, sku: 'API-REFUND');

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);

        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());

        $payment = Payment::query()->first();
        $this->post(route('storefront.payment.pay', $payment));

        $this->actingAs(User::query()->first())
            ->postJson(route('api.v1.payments.refund', $payment))
            ->assertOk()
            ->assertJsonPath('data.status', 'refunded');
    }
}
