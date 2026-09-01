<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Webhooks\Models\Webhook;
use Commerce\Webhooks\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class WebhookRetryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_retry_failed_webhook_delivery(): void
    {
        Http::fake(['https://hooks.example.test/*' => Http::response('ok', 200)]);

        $webhook = Webhook::query()->create([
            'name' => 'Retry test',
            'url' => 'https://hooks.example.test/retry',
            'secret' => 'test-secret',
            'events' => ['product.updated'],
            'is_active' => true,
        ]);

        $delivery = WebhookDelivery::query()->create([
            'webhook_id' => $webhook->id,
            'event_name' => 'product.updated',
            'payload' => [
                'event' => 'product.updated',
                'occurred_at' => now()->toAtomString(),
                'data' => ['product_uuid' => 'test-uuid'],
            ],
            'status' => WebhookDelivery::STATUS_FAILED,
            'error_message' => 'HTTP 500',
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.webhooks.deliveries.retry', [$webhook, $delivery]))
            ->assertRedirect();

        Http::assertSent(fn ($request) => $request->url() === 'https://hooks.example.test/retry');

        $delivery->refresh();
        $this->assertSame(WebhookDelivery::STATUS_SUCCESS, $delivery->status);
    }
}
