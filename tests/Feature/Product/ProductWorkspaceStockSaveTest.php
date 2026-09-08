<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Inventory\Models\InventoryItem;
use Commerce\Inventory\Models\StockMovement;
use Commerce\Inventory\Services\InventoryQueryService;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductWorkspaceStockSaveTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->user = User::query()->firstOrFail();
    }

    public function test_create_simple_tracked_sets_on_hand_via_movement(): void
    {
        $response = $this->createWorkspace([
            'product' => $this->productPayload('Tracked Simple', [
                'type' => 'simple',
                'trackInventory' => true,
                'backorderPolicy' => 'notify',
                'sku' => 'SIMPLE-TRACKED',
                'price' => '25',
                'onHand' => 10,
            ]),
            'variants' => [],
        ]);

        $response->assertCreated();
        $product = Product::query()->where('name', 'Tracked Simple')->firstOrFail();
        $variant = $product->defaultVariant();
        $item = InventoryItem::query()->where('purchasable_uuid', $variant?->uuid)->firstOrFail();

        $this->assertSame('simple', $product->type);
        $this->assertSame('notify', $product->backorder_policy);
        $this->assertSame(10, $item->on_hand);
        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $item->id,
            'type' => 'adjustment',
            'quantity' => 10,
            'reason' => 'Product workspace',
        ]);
    }

    public function test_create_simple_untracked_has_no_inventory_item(): void
    {
        $this->createWorkspace([
            'product' => $this->productPayload('Untracked Simple', [
                'type' => 'simple',
                'trackInventory' => false,
                'sku' => 'SIMPLE-UNTRACKED',
                'price' => '10',
            ]),
            'variants' => [],
        ])->assertCreated();

        $variant = Product::query()->where('name', 'Untracked Simple')->firstOrFail()->defaultVariant();

        $this->assertFalse((bool) $variant?->track_inventory);
        $this->assertDatabaseMissing('inventory_items', ['purchasable_uuid' => $variant?->uuid]);
    }

    public function test_create_variable_does_not_infer_type_from_one_variant(): void
    {
        $this->createWorkspace([
            'product' => $this->productPayload('One Variant Variable', [
                'type' => 'variable',
                'trackInventory' => false,
            ]),
            'variants' => [$this->variantPayload('ONE-VARIANT')],
        ])->assertCreated();

        $this->assertSame(
            'variable',
            Product::query()->where('name', 'One Variant Variable')->firstOrFail()->type,
        );
    }

    public function test_blank_variant_skus_are_unique_and_auto(): void
    {
        $this->createWorkspace([
            'product' => $this->productPayload('Auto SKU Product', [
                'type' => 'variable',
                'trackInventory' => false,
            ]),
            'skuPattern' => 'AUTO',
            'variants' => [
                $this->variantPayload(' ', ['Color' => 'Red']),
                $this->variantPayload('', ['Color' => 'Red']),
            ],
        ])->assertCreated();

        $variants = Product::query()
            ->where('name', 'Auto SKU Product')
            ->firstOrFail()
            ->variants()
            ->get();

        $this->assertSame(['AUTO-RED', 'AUTO-RED-2'], $variants->pluck('sku')->all());
        $this->assertSame([true, true], $variants->pluck('sku_is_auto')->all());
    }

    public function test_duplicate_typed_sku_is_rejected(): void
    {
        $response = $this->createWorkspace([
            'product' => $this->productPayload('Duplicate SKU Product', [
                'type' => 'variable',
                'trackInventory' => false,
            ]),
            'variants' => [
                $this->variantPayload('DUPLICATE-SKU'),
                $this->variantPayload('DUPLICATE-SKU'),
            ],
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('products', ['name' => 'Duplicate SKU Product']);
    }

    public function test_enable_track_without_qty_fails_validation(): void
    {
        $this->createWorkspace([
            'product' => $this->productPayload('Enable Tracking', [
                'type' => 'simple',
                'trackInventory' => false,
                'sku' => 'ENABLE-TRACKING',
                'price' => '15',
            ]),
            'variants' => [],
        ])->assertCreated();

        $product = Product::query()->where('name', 'Enable Tracking')->firstOrFail();
        $variant = $product->defaultVariant();
        $payload = [
            'product' => $this->productPayload('Enable Tracking', [
                'type' => 'simple',
                'trackInventory' => true,
            ]),
            'variants' => [$this->variantPayload('ENABLE-TRACKING', [], $variant?->uuid)],
        ];

        $this->updateWorkspace($product, $payload)->assertUnprocessable();
        $this->assertDatabaseMissing('inventory_items', ['purchasable_uuid' => $variant?->uuid]);

        $payload['product']['onHand'] = 7;
        $this->updateWorkspace($product, $payload)->assertOk();

        $item = InventoryItem::query()->where('purchasable_uuid', $variant?->uuid)->firstOrFail();
        $this->assertSame(7, $item->on_hand);
        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $item->id,
            'quantity' => 7,
            'reason' => 'Product workspace',
        ]);
    }

    public function test_tracked_on_hand_rejects_invalid_value_but_accepts_explicit_zero(): void
    {
        $this->createWorkspace([
            'product' => $this->productPayload('Invalid Tracked Quantity', [
                'type' => 'simple',
                'trackInventory' => true,
                'sku' => 'INVALID-TRACKED-QUANTITY',
                'price' => '15',
                'onHand' => 'invalid',
            ]),
            'variants' => [],
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('products', ['name' => 'Invalid Tracked Quantity']);

        $this->createWorkspace([
            'product' => $this->productPayload('Zero Tracked Quantity', [
                'type' => 'simple',
                'trackInventory' => true,
                'sku' => 'ZERO-TRACKED-QUANTITY',
                'price' => '15',
                'onHand' => 0,
            ]),
            'variants' => [],
        ])->assertCreated();

        $variant = Product::query()->where('name', 'Zero Tracked Quantity')->firstOrFail()->defaultVariant();
        $this->assertDatabaseHas('inventory_items', [
            'purchasable_uuid' => $variant?->uuid,
            'on_hand' => 0,
            'reserved' => 0,
        ]);
    }

    public function test_new_tracked_variant_gets_zero_inventory_item(): void
    {
        $this->createWorkspace([
            'product' => $this->productPayload('Tracked Variable', [
                'type' => 'variable',
                'trackInventory' => true,
            ]),
            'variants' => [
                $this->variantPayload('TRACKED-ONE', ['Size' => 'S'], null, 2),
                $this->variantPayload('TRACKED-TWO', ['Size' => 'M'], null, 3),
            ],
        ])->assertCreated();

        $product = Product::query()->where('name', 'Tracked Variable')->firstOrFail();
        $existing = $product->variants()->get();
        $this->updateWorkspace($product, [
            'product' => $this->productPayload('Tracked Variable', [
                'type' => 'variable',
                'trackInventory' => true,
            ]),
            'variants' => [
                $this->variantPayload('TRACKED-ONE', ['Size' => 'S'], $existing[0]->uuid),
                $this->variantPayload('TRACKED-TWO', ['Size' => 'M'], $existing[1]->uuid),
                $this->variantPayload('TRACKED-THREE', ['Size' => 'L']),
            ],
        ])->assertOk();

        $newVariant = ProductVariant::query()->where('sku', 'TRACKED-THREE')->firstOrFail();
        $this->assertDatabaseHas('inventory_items', [
            'purchasable_uuid' => $newVariant->uuid,
            'on_hand' => 0,
            'reserved' => 0,
        ]);
    }

    public function test_uncheck_track_keeps_item_and_movements(): void
    {
        $this->createWorkspace([
            'product' => $this->productPayload('Stop Tracking', [
                'type' => 'simple',
                'trackInventory' => true,
                'sku' => 'STOP-TRACKING',
                'price' => '20',
                'onHand' => 4,
            ]),
            'variants' => [],
        ])->assertCreated();

        $product = Product::query()->where('name', 'Stop Tracking')->firstOrFail();
        $variant = $product->defaultVariant();
        $item = InventoryItem::query()->where('purchasable_uuid', $variant?->uuid)->firstOrFail();
        $movementCount = StockMovement::query()->where('inventory_item_id', $item->id)->count();

        $this->updateWorkspace($product, [
            'product' => $this->productPayload('Stop Tracking', [
                'type' => 'simple',
                'trackInventory' => false,
            ]),
            'variants' => [$this->variantPayload('STOP-TRACKING', [], $variant?->uuid)],
        ])->assertOk();

        $this->assertFalse((bool) $variant?->fresh()?->track_inventory);
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'on_hand' => 4]);
        $this->assertSame($movementCount, StockMovement::query()->where('inventory_item_id', $item->id)->count());
        $this->assertFalse(
            app(InventoryQueryService::class)->paginate()->getCollection()->contains('id', $item->id),
        );
    }

    public function test_variable_to_simple_is_blocked_when_extra_variant_has_reserved_stock(): void
    {
        $this->createWorkspace([
            'product' => $this->productPayload('Guarded Variable', [
                'type' => 'variable',
                'trackInventory' => true,
            ]),
            'variants' => [
                $this->variantPayload('GUARD-KEEP', [], null, 2),
                $this->variantPayload('GUARD-BLOCK', [], null, 2),
            ],
        ])->assertCreated();

        $product = Product::query()->where('name', 'Guarded Variable')->firstOrFail();
        $variants = $product->variants()->get();
        app(InventoryServiceInterface::class)->reserve($variants[1]->uuid, 1);

        $this->updateWorkspace($product, [
            'product' => $this->productPayload('Guarded Variable', [
                'type' => 'simple',
                'trackInventory' => true,
                'onHand' => 2,
            ]),
            'variants' => [$this->variantPayload('GUARD-KEEP', [], $variants[0]->uuid, 2)],
        ])->assertUnprocessable();

        $this->assertSame('variable', $product->fresh()->type);
        $this->assertSame(2, $product->variants()->count());
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function createWorkspace(array $workspace)
    {
        return $this->actingAs($this->user)->postJson(
            route('api.v1.admin.products.workspace.store'),
            $this->requestPayload($workspace),
        );
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function updateWorkspace(Product $product, array $workspace)
    {
        return $this->actingAs($this->user)->putJson(
            route('api.v1.admin.products.workspace.update', $product->uuid),
            $this->requestPayload($workspace),
        );
    }

    /**
     * @param  array<string, mixed>  $workspace
     * @return array<string, mixed>
     */
    private function requestPayload(array $workspace): array
    {
        return [
            'name' => $workspace['product']['name'],
            'status' => 'published',
            'visibility' => 'public',
            'workspace_payload' => array_merge([
                'options' => [],
                'variants' => [],
                'media' => ['productUuids' => []],
            ], $workspace),
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productPayload(string $name, array $overrides = []): array
    {
        return array_merge([
            'name' => $name,
            'status' => 'published',
            'visibility' => 'public',
            'type' => 'simple',
            'backorderPolicy' => 'deny',
            'trackInventory' => false,
        ], $overrides);
    }

    /**
     * @param  array<string, string>  $options
     * @return array<string, mixed>
     */
    private function variantPayload(
        string $sku,
        array $options = [],
        ?string $uuid = null,
        ?int $onHand = null,
    ): array {
        return array_filter([
            'uuid' => $uuid,
            'name' => 'Variant '.$sku,
            'sku' => $sku,
            'price' => '10',
            'status' => 'active',
            'options' => $options,
            'isDefault' => false,
            'onHand' => $onHand,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
