<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Product\Models\Product;
use Commerce\Webhooks\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductWebhookTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_product_updated_webhook_is_delivered_on_workspace_save(): void
    {
        Http::fake(['https://hooks.example.test/*' => Http::response('ok', 200)]);

        Webhook::query()->create([
            'name' => 'Product updates',
            'url' => 'https://hooks.example.test/products',
            'secret' => 'test-secret',
            'events' => ['product.updated'],
            'is_active' => true,
        ]);

        $variant = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'WH-UPD-001');
        $product = $variant->product;

        $payload = $this->workspacePayload($product, 'Updated Name');

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect();

        Http::assertSent(function ($request) use ($product): bool {
            $body = $request->data();

            return $request->url() === 'https://hooks.example.test/products'
                && ($body['event'] ?? null) === 'product.updated'
                && ($body['data']['product_uuid'] ?? null) === $product->uuid;
        });
    }

    public function test_product_archived_webhook_is_delivered(): void
    {
        Http::fake(['https://hooks.example.test/*' => Http::response('ok', 200)]);

        Webhook::query()->create([
            'name' => 'Product archive',
            'url' => 'https://hooks.example.test/archive',
            'secret' => 'test-secret',
            'events' => ['product.archived'],
            'is_active' => true,
        ]);

        $variant = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'WH-ARC-001');
        $product = $variant->product;

        $this->actingAs(User::query()->first())
            ->post(route('admin.products.archive', $product))
            ->assertRedirect();

        Http::assertSent(function ($request) use ($product): bool {
            $body = $request->data();

            return $request->url() === 'https://hooks.example.test/archive'
                && ($body['event'] ?? null) === 'product.archived'
                && ($body['data']['product_uuid'] ?? null) === $product->uuid;
        });
    }

    public function test_product_deleted_webhook_is_delivered(): void
    {
        Http::fake(['https://hooks.example.test/*' => Http::response('ok', 200)]);

        Webhook::query()->create([
            'name' => 'Product delete',
            'url' => 'https://hooks.example.test/delete',
            'secret' => 'test-secret',
            'events' => ['product.deleted'],
            'is_active' => true,
        ]);

        $variant = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'WH-DEL-001');
        $product = $variant->product;
        $uuid = $product->uuid;

        $this->actingAs(User::query()->first())
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect();

        Http::assertSent(function ($request) use ($uuid): bool {
            $body = $request->data();

            return $request->url() === 'https://hooks.example.test/delete'
                && ($body['event'] ?? null) === 'product.deleted'
                && ($body['data']['product_uuid'] ?? null) === $uuid;
        });
    }

    public function test_product_unpublished_webhook_is_delivered_when_status_changes_to_draft(): void
    {
        Http::fake(['https://hooks.example.test/*' => Http::response('ok', 200)]);

        Webhook::query()->create([
            'name' => 'Product unpublished',
            'url' => 'https://hooks.example.test/unpublish',
            'secret' => 'test-secret',
            'events' => ['product.unpublished'],
            'is_active' => true,
        ]);

        $variant = $this->createPurchasableProduct(price: 50, stock: 2, sku: 'WH-UNPUB-001');
        $product = $variant->product;
        $product->update(['status' => 'published', 'published_at' => now()]);

        $payload = $this->workspacePayload($product, $product->name);
        $payload['status'] = 'draft';

        $this->actingAs(User::query()->first())
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect();

        Http::assertSent(function ($request) use ($product): bool {
            $body = $request->data();

            return $request->url() === 'https://hooks.example.test/unpublish'
                && ($body['event'] ?? null) === 'product.unpublished'
                && ($body['data']['product_uuid'] ?? null) === $product->uuid;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function workspacePayload(Product $product, string $name): array
    {
        $variant = $product->defaultVariant();

        return [
            'name' => $name,
            'status' => 'published',
            'visibility' => 'public',
            'workspace_payload' => json_encode([
                'product' => [
                    'name' => $name,
                    'status' => 'published',
                    'visibility' => 'public',
                ],
                'options' => [],
                'variants' => [[
                    'uuid' => $variant?->uuid,
                    'name' => $variant?->name ?? 'Default',
                    'sku' => $variant?->sku,
                    'price' => (string) $variant?->price,
                    'status' => 'active',
                    'options' => [],
                    'isDefault' => true,
                ]],
                'media' => ['productUuids' => []],
            ]),
        ];
    }
}
