<?php

declare(strict_types=1);

namespace Commerce\Webhooks\Services;

use Commerce\Contracts\Event\DomainEventInterface;
use Commerce\Webhooks\Models\Webhook;
use Commerce\Webhooks\Models\WebhookDelivery;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class WebhookDispatcher
{
    public function dispatchFromDomainEvent(DomainEventInterface $event): void
    {
        if (! (bool) config('webhooks.enabled', true)) {
            return;
        }

        $this->dispatch(
            eventName: $event->getEventName(),
            payload: $event->toPayload(),
            occurredAt: $event->getOccurredAt(),
            tenantId: $event->getTenantId(),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(
        string $eventName,
        array $payload,
        ?\DateTimeImmutable $occurredAt = null,
        ?int $tenantId = null,
    ): void {
        if (! (bool) config('webhooks.enabled', true)) {
            return;
        }

        $webhooks = Webhook::query()
            ->where('is_active', true)
            ->whereJsonContains('events', $eventName)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->deliver($webhook, $eventName, $payload, $occurredAt, $tenantId);
        }
    }

    public function retry(WebhookDelivery $delivery): WebhookDelivery
    {
        $webhook = $delivery->webhook;

        if ($webhook === null) {
            throw new \RuntimeException('Webhook not found for delivery.');
        }

        $envelope = $delivery->payload;

        return $this->deliver(
            webhook: $webhook,
            eventName: (string) ($envelope['event'] ?? $delivery->event_name),
            payload: (array) ($envelope['data'] ?? []),
            occurredAt: isset($envelope['occurred_at'])
                ? new \DateTimeImmutable((string) $envelope['occurred_at'])
                : null,
            tenantId: isset($envelope['tenant_id']) ? (int) $envelope['tenant_id'] : null,
            existingDelivery: $delivery,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function deliver(
        Webhook $webhook,
        string $eventName,
        array $payload,
        ?\DateTimeImmutable $occurredAt = null,
        ?int $tenantId = null,
        ?WebhookDelivery $existingDelivery = null,
    ): WebhookDelivery {
        $envelope = [
            'event' => $eventName,
            'occurred_at' => ($occurredAt ?? new \DateTimeImmutable)->format(\DateTimeInterface::ATOM),
            'tenant_id' => $tenantId,
            'data' => $payload,
        ];

        $body = json_encode($envelope, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $body, $webhook->secret);
        $header = (string) config('webhooks.signature_header', 'X-Commerce-Signature');

        $delivery = $existingDelivery ?? WebhookDelivery::query()->create([
            'webhook_id' => $webhook->id,
            'event_name' => $eventName,
            'payload' => $envelope,
            'status' => WebhookDelivery::STATUS_PENDING,
        ]);

        $startedAt = microtime(true);

        try {
            $response = Http::timeout((int) config('webhooks.timeout_seconds', 10))
                ->withHeaders([
                    $header => $signature,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Commerce-Framework-Webhooks/1.0',
                ])
                ->withBody($body, 'application/json')
                ->post($webhook->url);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $responseBody = $response->body();
            $truncatedBody = strlen($responseBody) > 2000
                ? substr($responseBody, 0, 2000) . '...'
                : $responseBody;

            if ($response->successful()) {
                $delivery->update([
                    'status' => WebhookDelivery::STATUS_SUCCESS,
                    'response_status' => $response->status(),
                    'response_body' => $truncatedBody !== '' ? $truncatedBody : null,
                    'error_message' => null,
                    'duration_ms' => $durationMs,
                    'delivered_at' => now(),
                ]);
            } else {
                $delivery->update([
                    'status' => WebhookDelivery::STATUS_FAILED,
                    'response_status' => $response->status(),
                    'response_body' => $truncatedBody !== '' ? $truncatedBody : null,
                    'error_message' => 'HTTP ' . $response->status(),
                    'duration_ms' => $durationMs,
                    'delivered_at' => now(),
                ]);
            }
        } catch (ConnectionException $exception) {
            $delivery->update([
                'status' => WebhookDelivery::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'delivered_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $delivery->update([
                'status' => WebhookDelivery::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'delivered_at' => now(),
            ]);
        }

        return $delivery->fresh();
    }
}
