<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Inventory\Models\InventoryItem;
use Commerce\Orders\Contracts\OrderServiceInterface;
use Commerce\Orders\DTO\CreateOrderData;
use Commerce\Orders\DTO\OrderLineData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StockPolicyEvaluatorTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_untracked_variant_skips_reserve(): void
    {
        $variant = $this->createPurchasableProduct(stock: 3);
        $variant->update(['track_inventory' => false]);
        InventoryItem::query()->where('purchasable_uuid', $variant->uuid)->delete();

        $level = app(InventoryServiceInterface::class)->reserve($variant->uuid, 5);

        $this->assertSame(0, $level->getOnHand());
        $this->assertSame(0, $level->getReserved());
        $this->assertDatabaseMissing('inventory_items', ['purchasable_uuid' => $variant->uuid]);
    }

    public function test_deny_zero_available_cannot_reserve(): void
    {
        $variant = $this->createPurchasableProduct(stock: 1);
        app(InventoryServiceInterface::class)->setOnHand($variant->uuid, 0);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Insufficient stock to reserve.');

        app(InventoryServiceInterface::class)->reserve($variant->uuid, 1);
    }

    public function test_allow_zero_available_can_reserve_beyond_on_hand(): void
    {
        $variant = $this->createPurchasableProduct(stock: 1);
        $variant->product->update(['backorder_policy' => 'allow']);
        app(InventoryServiceInterface::class)->setOnHand($variant->uuid, 0);

        $level = app(InventoryServiceInterface::class)->reserve($variant->uuid, 5);

        $this->assertSame(0, $level->getOnHand());
        $this->assertSame(5, $level->getReserved());
    }

    public function test_notify_zero_available_can_reserve_beyond_on_hand(): void
    {
        $variant = $this->createPurchasableProduct(stock: 1);
        $variant->product->update(['backorder_policy' => 'notify']);
        app(InventoryServiceInterface::class)->setOnHand($variant->uuid, 0);

        $level = app(InventoryServiceInterface::class)->reserve($variant->uuid, 5);

        $this->assertSame(0, $level->getOnHand());
        $this->assertSame(5, $level->getReserved());
    }

    public function test_sale_floors_on_hand_at_zero(): void
    {
        $variant = $this->createPurchasableProduct(stock: 1);
        $variant->product->update(['backorder_policy' => 'allow']);
        app(InventoryServiceInterface::class)->setOnHand($variant->uuid, 0);
        app(InventoryServiceInterface::class)->reserve($variant->uuid, 5);

        $level = app(InventoryServiceInterface::class)->sale($variant->uuid, 5);

        $this->assertSame(0, $level->getOnHand());
        $this->assertSame(0, $level->getReserved());
        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => InventoryItem::query()
                ->where('purchasable_uuid', $variant->uuid)
                ->value('id'),
            'type' => 'sale',
            'quantity' => -5,
            'on_hand_after' => 0,
        ]);
    }

    public function test_deny_sale_rejects_quantity_beyond_remaining_on_hand(): void
    {
        $variant = $this->createPurchasableProduct(stock: 5);
        $inventory = app(InventoryServiceInterface::class);

        $firstSale = $inventory->sale($variant->uuid, 3);
        $this->assertSame(2, $firstSale->getOnHand());
        $this->assertSame(0, $firstSale->getReserved());

        try {
            $inventory->sale($variant->uuid, 3);
            $this->fail('Expected a deny sale beyond on-hand to fail.');
        } catch (DomainException $exception) {
            $this->assertSame('Insufficient stock for sale.', $exception->getMessage());
        }

        $level = app(InventoryQueryServiceInterface::class)->getStockLevel($variant->uuid);
        $this->assertSame(2, $level->getOnHand());
        $this->assertSame(0, $level->getReserved());
    }

    public function test_confirm_sale_consumes_only_its_quantity_from_aggregate_reserved(): void
    {
        $variant = $this->createPurchasableProduct(stock: 10);
        $inventory = app(InventoryServiceInterface::class);
        $inventory->reserve($variant->uuid, 10);
        $orders = app(OrderServiceInterface::class);
        $order = $orders->create(new CreateOrderData(
            lines: [new OrderLineData(
                purchasableUuid: $variant->uuid,
                quantity: 5,
            )],
        ));

        $orders->confirm($order->uuid);

        $level = app(InventoryQueryServiceInterface::class)->getStockLevel($variant->uuid);
        $this->assertSame(5, $level->getOnHand());
        $this->assertSame(5, $level->getReserved());
    }

    public function test_allow_backorder_can_confirm_fully_reserved_order_at_zero_on_hand(): void
    {
        $variant = $this->createPurchasableProduct(stock: 1);
        $variant->product->update(['backorder_policy' => 'allow']);
        $inventory = app(InventoryServiceInterface::class);
        $inventory->setOnHand($variant->uuid, 0);
        $inventory->reserve($variant->uuid, 5);
        $orders = app(OrderServiceInterface::class);
        $order = $orders->create(new CreateOrderData(
            lines: [new OrderLineData($variant->uuid, 5)],
        ));

        $confirmed = $orders->confirm($order->uuid);

        $level = app(InventoryQueryServiceInterface::class)->getStockLevel($variant->uuid);
        $this->assertSame('confirmed', $confirmed->status);
        $this->assertSame(0, $level->getOnHand());
        $this->assertSame(0, $level->getReserved());
    }

    public function test_deny_policy_rejects_confirm_when_on_hand_cannot_cover_quantity(): void
    {
        $variant = $this->createPurchasableProduct(stock: 1);
        app(InventoryServiceInterface::class)->setOnHand($variant->uuid, 0);
        $orders = app(OrderServiceInterface::class);
        $order = $orders->create(new CreateOrderData(
            lines: [new OrderLineData($variant->uuid, 1)],
        ));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Insufficient stock for {$variant->name}.");

        $orders->confirm($order->uuid);
    }

    public function test_untracked_variant_can_confirm_without_inventory_item(): void
    {
        $variant = $this->createPurchasableProduct(stock: 1);
        $variant->update(['track_inventory' => false]);
        InventoryItem::query()->where('purchasable_uuid', $variant->uuid)->delete();
        $orders = app(OrderServiceInterface::class);
        $order = $orders->create(new CreateOrderData(
            lines: [new OrderLineData($variant->uuid, 1)],
        ));

        $confirmed = $orders->confirm($order->uuid);

        $this->assertSame('confirmed', $confirmed->status);
        $this->assertDatabaseMissing('inventory_items', ['purchasable_uuid' => $variant->uuid]);
    }

    public function test_paginate_omits_untracked_inventory_items(): void
    {
        $tracked = $this->createPurchasableProduct(stock: 2);
        $untracked = $this->createPurchasableProduct(stock: 2);
        $untracked->update(['track_inventory' => false]);

        $paginator = app(InventoryQueryServiceInterface::class)->paginate();
        $listedUuids = $paginator->getCollection()->pluck('purchasable_uuid')->all();

        $this->assertContains($tracked->uuid, $listedUuids);
        $this->assertNotContains($untracked->uuid, $listedUuids);
    }

    public function test_availability_for_purchasable_returns_null_when_untracked(): void
    {
        $variant = $this->createPurchasableProduct(stock: 2);
        $variant->update(['track_inventory' => false]);

        $availability = app(InventoryQueryServiceInterface::class)
            ->availabilityForPurchasable($variant->uuid);

        $this->assertNull($availability);
    }
}
