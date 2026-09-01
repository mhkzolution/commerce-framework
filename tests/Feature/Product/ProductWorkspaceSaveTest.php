<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Marketplace\Models\Seller;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductWorkspaceSaveTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_create_product_via_workspace_payload(): void
    {
        $payload = $this->workspacePayload([
            'name' => 'Workspace Tee',
            'variants' => [
                [
                    'name' => 'Default',
                    'sku' => 'WS-TEE-001',
                    'price' => '590',
                    'cost' => '300',
                    'weight' => '250',
                    'status' => 'active',
                    'options' => [],
                    'isDefault' => true,
                ],
            ],
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.products.store'), $payload)
            ->assertRedirect();

        $product = Product::query()->where('name', 'Workspace Tee')->firstOrFail();
        $variant = $product->defaultVariant();

        $this->assertSame('simple', $product->type);
        $this->assertSame('WS-TEE-001', $variant?->sku);
        $this->assertSame('590.00', (string) $variant?->price);
        $this->assertSame('300.00', (string) $variant?->cost);
        $this->assertSame('250.000', (string) $variant?->weight);
    }

    public function test_admin_can_create_variable_product_with_multiple_variants(): void
    {
        $payload = $this->workspacePayload([
            'name' => 'Matrix Hoodie',
            'options' => [
                ['id' => 'opt_color', 'name' => 'Color', 'values' => ['Red', 'Blue']],
                ['id' => 'opt_size', 'name' => 'Size', 'values' => ['S', 'M']],
            ],
            'variants' => [
                [
                    'name' => 'Red / S',
                    'sku' => 'HOODIE-RED-S',
                    'price' => '100',
                    'options' => ['color' => 'Red', 'size' => 'S'],
                    'isDefault' => true,
                ],
                [
                    'name' => 'Red / M',
                    'sku' => 'HOODIE-RED-M',
                    'price' => '110',
                    'options' => ['color' => 'Red', 'size' => 'M'],
                ],
                [
                    'name' => 'Blue / S',
                    'sku' => 'HOODIE-BLU-S',
                    'price' => '105',
                    'options' => ['color' => 'Blue', 'size' => 'S'],
                ],
                [
                    'name' => 'Blue / M',
                    'sku' => 'HOODIE-BLU-M',
                    'price' => '115',
                    'options' => ['color' => 'Blue', 'size' => 'M'],
                ],
            ],
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.products.store'), $payload)
            ->assertRedirect();

        $product = Product::query()->where('name', 'Matrix Hoodie')->with('variants')->firstOrFail();

        $this->assertSame('variable', $product->type);
        $this->assertCount(4, $product->variants);
        $this->assertSame(['color' => 'Red', 'size' => 'S'], $product->variants->firstWhere('sku', 'HOODIE-RED-S')?->meta['options']);
    }

    public function test_admin_can_update_product_variants_via_workspace_payload(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 4, sku: 'WS-UPDATE-001');
        $product = $variant->product;

        $payload = $this->workspacePayload([
            'name' => $product->name,
            'variants' => [
                [
                    'uuid' => $variant->uuid,
                    'name' => 'Updated Default',
                    'sku' => 'WS-UPDATE-001',
                    'price' => '75',
                    'cost' => '40',
                    'weight' => '180',
                    'status' => 'active',
                    'options' => [],
                    'isDefault' => true,
                ],
            ],
        ]);

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect(route('admin.products.edit', $product));

        $variant->refresh();

        $this->assertSame('Updated Default', $variant->name);
        $this->assertSame('75.00', (string) $variant->price);
    }

    public function test_workspace_save_removes_variants_not_in_payload(): void
    {
        $variant = $this->createPurchasableProduct(price: 10, sku: 'WS-KEEP-001');
        $product = $variant->product;

        $extra = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'WS-REMOVE-002',
            'name' => 'Remove me',
            'price' => 20,
            'is_default' => false,
            'position' => 1,
        ]);

        $payload = $this->workspacePayload([
            'name' => $product->name,
            'variants' => [
                [
                    'uuid' => $variant->uuid,
                    'name' => 'Keep',
                    'sku' => 'WS-KEEP-001',
                    'price' => '10',
                    'options' => [],
                    'isDefault' => true,
                ],
            ],
        ]);

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect();

        $this->assertSoftDeleted('product_variants', ['uuid' => $extra->uuid]);
        $product->refresh();
        $this->assertSame('simple', $product->type);
    }

    public function test_workspace_save_persists_custom_meta_json(): void
    {
        $payload = $this->workspacePayload([
            'name' => 'Meta Product',
            'variants' => [
                [
                    'name' => 'Default',
                    'sku' => 'META-001',
                    'price' => '100',
                    'status' => 'active',
                    'options' => [],
                    'isDefault' => true,
                ],
            ],
        ]);

        $payload['meta'] = [
            'external_id' => 'ERP-999',
            'custom_json' => '{"warehouse":"BKK-1"}',
        ];

        $this->actingAs(User::query()->first())
            ->post(route('admin.products.store'), $payload)
            ->assertRedirect();

        $product = Product::query()->where('name', 'Meta Product')->firstOrFail();

        $this->assertSame('ERP-999', $product->meta['external_id']);
        $this->assertSame(['warehouse' => 'BKK-1'], $product->meta['custom']);
    }

    public function test_workspace_save_persists_seller_uuid_from_request(): void
    {
        if (! Schema::hasTable('marketplace_sellers')) {
            $this->markTestSkipped('Marketplace sellers table is not available.');
        }

        $seller = Seller::query()->create([
            'name' => 'Workspace Seller',
            'slug' => 'workspace-seller',
            'email' => 'workspace-seller@example.com',
            'commission_rate' => 1000,
            'status' => 'active',
        ]);

        $variant = $this->createPurchasableProduct(price: 50, stock: 4, sku: 'WS-SELLER-001');
        $product = $variant->product;

        $payload = $this->workspacePayload([
            'name' => $product->name,
            'variants' => [
                [
                    'uuid' => $variant->uuid,
                    'name' => 'Default',
                    'sku' => 'WS-SELLER-001',
                    'price' => '50',
                    'status' => 'active',
                    'options' => [],
                    'isDefault' => true,
                ],
            ],
        ]);

        $payload['seller_uuid'] = $seller->uuid;

        $workspace = json_decode((string) $payload['workspace_payload'], true);
        $workspace['product']['sellerUuid'] = '';
        $payload['workspace_payload'] = json_encode($workspace);

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();

        $this->assertSame($seller->uuid, $product->seller_uuid);
    }

    public function test_workspace_save_persists_seller_uuid_from_workspace_payload(): void
    {
        if (! Schema::hasTable('marketplace_sellers')) {
            $this->markTestSkipped('Marketplace sellers table is not available.');
        }

        $seller = Seller::query()->create([
            'name' => 'Payload Seller',
            'slug' => 'payload-seller',
            'email' => 'payload-seller@example.com',
            'commission_rate' => 1000,
            'status' => 'active',
        ]);

        $variant = $this->createPurchasableProduct(price: 50, stock: 4, sku: 'WS-SELLER-002');
        $product = $variant->product;

        $payload = $this->workspacePayload([
            'name' => $product->name,
            'variants' => [
                [
                    'uuid' => $variant->uuid,
                    'name' => 'Default',
                    'sku' => 'WS-SELLER-002',
                    'price' => '50',
                    'status' => 'active',
                    'options' => [],
                    'isDefault' => true,
                ],
            ],
        ]);

        $workspace = json_decode((string) $payload['workspace_payload'], true);
        $workspace['product']['sellerUuid'] = $seller->uuid;
        $payload['workspace_payload'] = json_encode($workspace);

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();

        $this->assertSame($seller->uuid, $product->seller_uuid);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function workspacePayload(array $overrides = []): array
    {
        $variants = $overrides['variants'] ?? [[
            'name' => 'Default',
            'sku' => 'SKU-'.strtoupper(substr(uniqid(), -6)),
            'price' => '99',
            'status' => 'active',
            'options' => [],
            'isDefault' => true,
        ]];

        unset($overrides['variants']);

        $workspace = array_merge([
            'product' => [
                'name' => $overrides['name'] ?? 'Workspace Product',
                'slug' => $overrides['slug'] ?? '',
                'status' => 'published',
                'visibility' => 'public',
            ],
            'options' => $overrides['options'] ?? [],
            'variants' => $variants,
            'media' => ['productUuids' => []],
        ], array_diff_key($overrides, array_flip(['name', 'slug', 'options'])));

        return array_merge([
            'name' => $workspace['product']['name'],
            'status' => 'published',
            'visibility' => 'public',
            'workspace_payload' => json_encode($workspace),
        ], array_diff_key($overrides, ['options']));
    }
}
