<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductInventoryFormTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_set_stock_when_creating_product(): void
    {
        $this->actingAs(User::query()->first())
            ->post(route('admin.products.store'), $this->workspaceRequest([
                'name' => 'Stocked Product',
                'variants' => [[
                    'name' => 'Default',
                    'sku' => 'FORM-STOCK-001',
                    'price' => '99',
                    'options' => [],
                    'isDefault' => true,
                ]],
            ]))
            ->assertRedirect();

        $variant = ProductVariant::query()->where('sku', 'FORM-STOCK-001')->firstOrFail();
        $stock = app(InventoryQueryServiceInterface::class)->levelsForPurchasables([$variant->uuid]);

        $this->assertSame(0, $stock[$variant->uuid]->getOnHand());
    }

    public function test_admin_can_update_product_from_workspace_form(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 4, sku: 'FORM-STOCK-002');
        $product = $variant->product;

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.update', $product), $this->workspaceRequest([
                'name' => $product->name,
                'variants' => [[
                    'uuid' => $variant->uuid,
                    'name' => $product->name,
                    'sku' => $variant->sku,
                    'price' => '50',
                    'options' => [],
                    'isDefault' => true,
                ]],
            ]))
            ->assertRedirect(route('admin.products.edit', $product));

        $variant->refresh();
        $this->assertSame('50.00', (string) $variant->price);
    }

    public function test_create_and_edit_pages_show_workspace_ui(): void
    {
        $variant = $this->createPurchasableProduct(price: 10, stock: 2, sku: 'FORM-STOCK-003');
        $product = $variant->product;

        $this->actingAs(User::query()->first())
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('Save product')
            ->assertSee('Variant grid')
            ->assertSee('data-product-workspace-state', false);

        $this->actingAs(User::query()->first())
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Save product')
            ->assertSee('Variant grid')
            ->assertSee('variant-grid-row-template', false)
            ->assertSee($variant->sku, false)
            ->assertSee('data-variant-grid-body', false);
    }

    public function test_edit_page_workspace_form_submits_with_post_and_method_spoofing(): void
    {
        $variant = $this->createPurchasableProduct(price: 10, stock: 2, sku: 'FORM-NEST-001');
        $product = $variant->product;

        $this->actingAs(User::query()->first())
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('action="'.route('admin.products.update', $product).'" method="POST"', false)
            ->assertSee('name="_method"', false)
            ->assertSee('value="PUT"', false)
            ->assertSee('data-workspace-save', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function workspaceRequest(array $overrides = []): array
    {
        $name = $overrides['name'] ?? 'Workspace Product';
        $variants = $overrides['variants'] ?? [[
            'name' => 'Default',
            'sku' => 'SKU-'.strtoupper(substr(uniqid(), -6)),
            'price' => '99',
            'options' => [],
            'isDefault' => true,
        ]];

        unset($overrides['name'], $overrides['variants']);

        return array_merge([
            'name' => $name,
            'status' => 'published',
            'visibility' => 'public',
            'workspace_payload' => json_encode([
                'product' => ['name' => $name, 'status' => 'published', 'visibility' => 'public'],
                'variants' => $variants,
                'options' => [],
                'media' => ['productUuids' => []],
            ]),
        ], $overrides);
    }
}
