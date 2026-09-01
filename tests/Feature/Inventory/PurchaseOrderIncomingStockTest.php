<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use Commerce\Inventory\Models\PurchaseOrder;
use Commerce\Inventory\Models\PurchaseOrderLine;
use Commerce\Inventory\Services\IncomingStockQueryService;
use Commerce\Inventory\Services\PurchaseOrderService;
use Commerce\Product\Services\ProductWorkspaceStateBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class PurchaseOrderIncomingStockTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_incoming_stock_query_sums_open_purchase_order_lines(): void
    {
        $variant = $this->createPurchasableProduct(price: 100, stock: 5, sku: 'PO-IN-001');

        $order = PurchaseOrder::query()->create([
            'reference' => 'PO-1001',
            'status' => PurchaseOrder::STATUS_ORDERED,
        ]);

        PurchaseOrderLine::query()->create([
            'purchase_order_id' => $order->id,
            'purchasable_uuid' => $variant->uuid,
            'sku' => $variant->sku,
            'quantity_ordered' => 12,
            'quantity_received' => 3,
        ]);

        $incoming = app(IncomingStockQueryService::class)->incomingForPurchasables([$variant->uuid]);

        $this->assertSame(9, $incoming[$variant->uuid]);
    }

    public function test_received_purchase_orders_do_not_count_toward_incoming(): void
    {
        $variant = $this->createPurchasableProduct(price: 100, stock: 5, sku: 'PO-IN-002');

        $order = PurchaseOrder::query()->create([
            'reference' => 'PO-1002',
            'status' => PurchaseOrder::STATUS_RECEIVED,
        ]);

        PurchaseOrderLine::query()->create([
            'purchase_order_id' => $order->id,
            'purchasable_uuid' => $variant->uuid,
            'sku' => $variant->sku,
            'quantity_ordered' => 10,
            'quantity_received' => 10,
        ]);

        $incoming = app(IncomingStockQueryService::class)->incomingForPurchasables([$variant->uuid]);

        $this->assertSame([], $incoming);
    }

    public function test_workspace_state_includes_incoming_from_purchase_orders(): void
    {
        $variant = $this->createPurchasableProduct(price: 100, stock: 5, sku: 'PO-IN-003');
        $product = $variant->product->fresh(['variants']);

        $order = PurchaseOrder::query()->create([
            'reference' => 'PO-1003',
            'status' => PurchaseOrder::STATUS_PARTIAL,
        ]);

        PurchaseOrderLine::query()->create([
            'purchase_order_id' => $order->id,
            'purchasable_uuid' => $variant->uuid,
            'sku' => $variant->sku,
            'quantity_ordered' => 20,
            'quantity_received' => 0,
        ]);

        $state = app(ProductWorkspaceStateBuilder::class)->build($product);
        $variantState = collect($state['variants'])->firstWhere('uuid', $variant->uuid);

        $this->assertNotNull($variantState);
        $this->assertSame(20, $variantState['stock']['incoming']);
    }

    public function test_purchase_order_receive_updates_stock_and_closes_line(): void
    {
        $variant = $this->createPurchasableProduct(price: 100, stock: 2, sku: 'PO-IN-004');

        $order = app(PurchaseOrderService::class)->create('PO-1004', [
            ['sku' => $variant->sku, 'quantity' => 5],
        ]);

        $line = $order->lines->firstOrFail();

        app(PurchaseOrderService::class)->receiveLine($line->id, 5);

        $line->refresh();
        $order->refresh();

        $this->assertSame(5, $line->quantity_received);
        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $order->status);

        $incoming = app(IncomingStockQueryService::class)->incomingForPurchasables([$variant->uuid]);
        $this->assertSame([], $incoming);
    }

    public function test_purchase_order_cancel_removes_incoming_stock(): void
    {
        $variant = $this->createPurchasableProduct(price: 100, stock: 2, sku: 'PO-CANCEL-001');

        $order = app(PurchaseOrderService::class)->create('PO-CANCEL', [
            ['sku' => $variant->sku, 'quantity' => 8],
        ]);

        app(PurchaseOrderService::class)->cancel($order);

        $incoming = app(IncomingStockQueryService::class)->incomingForPurchasables([$variant->uuid]);
        $this->assertSame([], $incoming);
    }
}
