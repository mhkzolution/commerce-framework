<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Contracts\Order\OrderStatus;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Orders\Models\Order;
use Commerce\Orders\Models\OrderLineItem;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateVariantData;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;
use Commerce\Product\Services\ProductTypeChangeGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductTypeChangeGuardTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_blocks_when_extra_variant_has_reserved_stock(): void
    {
        [$product, $keptVariant, $extraVariant] = $this->createVariableProduct();

        $inventory = app(InventoryServiceInterface::class);
        $inventory->receive($extraVariant->uuid, 2);
        $inventory->reserve($extraVariant->uuid, 1);

        try {
            app(ProductTypeChangeGuard::class)->assertCanBecomeSimple($product, [$keptVariant->uuid]);
            $this->fail('Expected reserved stock to block the product type change.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString((string) $extraVariant->sku, $exception->getMessage());
        }
    }

    public function test_blocks_when_extra_variant_is_on_pending_order(): void
    {
        [$product, $keptVariant, $extraVariant] = $this->createVariableProduct();
        $this->createOrderLine($extraVariant, OrderStatus::Pending);

        try {
            app(ProductTypeChangeGuard::class)->assertCanBecomeSimple($product, [$keptVariant->uuid]);
            $this->fail('Expected a pending order to block the product type change.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString((string) $extraVariant->sku, $exception->getMessage());
        }
    }

    public function test_allows_when_only_completed_order_references_extra_variant(): void
    {
        [$product, $keptVariant, $extraVariant] = $this->createVariableProduct();
        $this->createOrderLine($extraVariant, OrderStatus::Completed);

        app(ProductTypeChangeGuard::class)->assertCanBecomeSimple($product, [$keptVariant->uuid]);

        $this->addToAssertionCount(1);
    }

    public function test_simple_to_variable_always_allowed(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'SIMPLE-TO-VARIABLE');

        app(ProductTypeChangeGuard::class)->assertCanBecomeVariable($variant->product);

        $this->addToAssertionCount(1);
    }

    /**
     * @return array{Product, ProductVariant, ProductVariant}
     */
    private function createVariableProduct(): array
    {
        $keptVariant = $this->createPurchasableProduct(sku: 'KEPT-SKU');
        $product = $keptVariant->product;
        $product->update(['type' => 'variable']);

        $extraVariant = app(ProductServiceInterface::class)->addVariant(new CreateVariantData(
            productUuid: $product->uuid,
            sku: 'BLOCKING-SKU',
            name: 'Blocking Variant',
            price: 2500,
            position: 1,
        ));

        return [$product->fresh(), $keptVariant, $extraVariant];
    }

    private function createOrderLine(ProductVariant $variant, OrderStatus $status): void
    {
        $order = Order::query()->create([
            'order_number' => 'TYPE-GUARD-' . strtoupper(substr(uniqid(), -8)),
            'status' => $status->value,
            'currency' => 'USD',
            'subtotal' => 2500,
            'grand_total' => 2500,
        ]);

        OrderLineItem::query()->create([
            'order_id' => $order->id,
            'purchasable_uuid' => $variant->uuid,
            'name' => (string) $variant->name,
            'quantity' => 1,
            'unit_price' => 2500,
            'line_total' => 2500,
        ]);
    }
}
