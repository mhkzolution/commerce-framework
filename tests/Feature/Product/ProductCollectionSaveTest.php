<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Catalog\Models\Collection;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductCollectionSaveTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_workspace_save_syncs_product_collections(): void
    {
        $collection = Collection::query()->create([
            'name' => 'Summer Sale',
            'slug' => 'summer-sale',
        ]);

        $payload = $this->workspacePayload([
            'name' => 'Collection Product',
            'collection_ids' => [$collection->id],
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.products.store'), $payload)
            ->assertRedirect();

        $product = Product::query()->where('name', 'Collection Product')->with('collections')->firstOrFail();

        $this->assertCount(1, $product->collections);
        $this->assertSame('Summer Sale', $product->collections->first()?->name);
    }

    public function test_workspace_save_updates_product_collections(): void
    {
        $variant = $this->createPurchasableProduct(price: 20, sku: 'COLL-001');
        $product = $variant->product;

        $first = Collection::query()->create(['name' => 'First', 'slug' => 'first']);
        $second = Collection::query()->create(['name' => 'Second', 'slug' => 'second']);

        $payload = $this->workspacePayload([
            'name' => $product->name,
            'collection_ids' => [$second->id],
            'variants' => [[
                'uuid' => $variant->uuid,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'price' => '20',
                'options' => [],
                'isDefault' => true,
            ]],
        ]);

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect();

        $product->refresh()->load('collections');

        $this->assertCount(1, $product->collections);
        $this->assertSame($second->id, $product->collections->first()?->id);
        $this->assertFalse($product->collections->contains('id', $first->id));
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

        $workspace = [
            'product' => [
                'name' => $overrides['name'] ?? 'Workspace Product',
                'status' => 'published',
                'visibility' => 'public',
            ],
            'options' => [],
            'variants' => $variants,
            'media' => ['productUuids' => []],
        ];

        return array_merge([
            'name' => $workspace['product']['name'],
            'status' => 'published',
            'visibility' => 'public',
            'collection_ids' => $overrides['collection_ids'] ?? [],
            'workspace_payload' => json_encode($workspace),
        ], array_diff_key($overrides, ['name', 'collection_ids']));
    }
}
