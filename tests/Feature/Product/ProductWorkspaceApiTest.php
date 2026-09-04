<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductWorkspaceApiTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_create_page_renders_workspace(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('data-product-workspace', false)
            ->assertSee('workspace_payload', false);
    }

    public function test_admin_can_fetch_product_workspace_via_api(): void
    {
        $variant = $this->createPurchasableProduct(price: 4500, sku: 'API-WS-001');
        $product = $variant->product;

        $this->actingAs(User::query()->first())
            ->getJson(route('api.v1.admin.products.workspace.show', $product->uuid))
            ->assertOk()
            ->assertJsonPath('data.uuid', $product->uuid)
            ->assertJsonPath('data.workspace.product.name', $product->name)
            ->assertJsonPath('data.workspace.variants.0.sku', 'API-WS-001');
    }

    public function test_admin_can_create_product_via_workspace_api(): void
    {
        $response = $this->actingAs(User::query()->first())
            ->postJson(route('api.v1.admin.products.workspace.store'), [
                'name' => 'API Workspace Product',
                'status' => 'published',
                'visibility' => 'public',
                'workspace_payload' => [
                    'product' => [
                        'name' => 'API Workspace Product',
                        'status' => 'published',
                        'visibility' => 'public',
                    ],
                    'options' => [],
                    'variants' => [[
                        'name' => 'Default',
                        'sku' => 'API-CREATE-001',
                        'price' => '120',
                        'status' => 'active',
                        'options' => [],
                        'isDefault' => true,
                    ]],
                    'media' => ['productUuids' => []],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'API Workspace Product');

        $product = Product::query()->where('name', 'API Workspace Product')->firstOrFail();
        $this->assertSame('API-CREATE-001', $product->defaultVariant()?->sku);
        $this->assertSame(12000, (int) $product->defaultVariant()?->price);
    }

    public function test_admin_can_update_product_via_workspace_api(): void
    {
        $variant = $this->createPurchasableProduct(price: 3000, sku: 'API-UPD-001');
        $product = $variant->product;

        $this->actingAs(User::query()->first())
            ->putJson(route('api.v1.admin.products.workspace.update', $product->uuid), [
                'name' => 'Updated API Product',
                'status' => 'published',
                'visibility' => 'public',
                'workspace_payload' => [
                    'product' => [
                        'name' => 'Updated API Product',
                        'status' => 'published',
                        'visibility' => 'public',
                    ],
                    'options' => [],
                    'variants' => [[
                        'uuid' => $variant->uuid,
                        'name' => 'Updated Variant',
                        'sku' => 'API-UPD-001',
                        'price' => '55',
                        'status' => 'active',
                        'options' => [],
                        'isDefault' => true,
                    ]],
                    'media' => ['productUuids' => []],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated API Product');

        $variant->refresh();
        $this->assertSame('Updated Variant', $variant->name);
        $this->assertSame(5500, (int) $variant->price);
    }
}
