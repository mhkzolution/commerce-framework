<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use Commerce\Orders\Models\Order;
use Commerce\Payment\Models\Payment;
use Commerce\Shipping\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class CheckoutFlowTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCheckoutDependencies();
    }

    public function test_happy_path_web_checkout_and_payment(): void
    {
        $variant = $this->createPurchasableProduct(price: 2500, stock: 10);

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 2,
        ])->assertRedirect(route('storefront.cart.index'));

        $response = $this->post(route('storefront.checkout.store'), $this->checkoutPayload());
        $response->assertRedirect();

        $order = Order::query()->where('customer_email', 'buyer@example.com')->first();
        $this->assertNotNull($order);
        $this->assertSame('pending', $order->status);

        $payment = Payment::query()->where('order_uuid', $order->uuid)->first();
        $this->assertNotNull($payment);
        $this->assertSame('pending', $payment->status);

        $this->post(route('storefront.payment.pay', $payment))
            ->assertRedirect(route('storefront.checkout.confirmation', $order));

        $order->refresh();
        $payment->refresh();

        $this->assertSame('confirmed', $order->status);
        $this->assertSame('paid', $payment->status);
        $this->assertDatabaseHas('inventory_items', [
            'purchasable_uuid' => $variant->uuid,
            'on_hand' => 8,
        ]);

        $this->get(route('storefront.cart.index'))
            ->assertOk()
            ->assertSee('Your cart is empty', false);
    }

    public function test_checkout_with_empty_cart_fails(): void
    {
        $this->post(route('storefront.checkout.store'), $this->checkoutPayload())
            ->assertRedirect(route('storefront.checkout'))
            ->assertSessionHasErrors('checkout');
    }

    public function test_insufficient_stock_at_checkout_fails(): void
    {
        $variant = $this->createPurchasableProduct(price: 1000, stock: 1);

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 5,
        ]);

        $this->post(route('storefront.checkout.store'), $this->checkoutPayload())
            ->assertRedirect(route('storefront.checkout'))
            ->assertSessionHasErrors('checkout');
    }

    public function test_payment_failure_cancels_order(): void
    {
        $variant = $this->createPurchasableProduct();

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);

        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());

        $order = Order::query()->first();
        $payment = Payment::query()->where('order_uuid', $order->uuid)->first();

        $this->post(route('storefront.payment.fail', $payment))
            ->assertRedirect(route('storefront.shop.index'));

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('failed', $payment->fresh()->status);
    }

    public function test_us_address_applies_tax(): void
    {
        $variant = $this->createPurchasableProduct(price: 10000, stock: 5);

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);

        $this->post(route('storefront.checkout.store'), $this->checkoutPayload());

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertGreaterThan(0, $order->tax_total);
    }

    public function test_missing_shipping_method_fails_validation(): void
    {
        $variant = $this->createPurchasableProduct();

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ]);

        $payload = $this->checkoutPayload();
        unset($payload['shipping_method_uuid']);

        $this->post(route('storefront.checkout.store'), $payload)
            ->assertSessionHasErrors('shipping_method_uuid');
    }
}
