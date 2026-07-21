<?php

declare(strict_types=1);

namespace Commerce\Core\Outbox;

use Commerce\Core\Models\OutboxMessage;
use Commerce\Core\Tenant\TenantContext;

final class OutboxRecorder
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function record(object $event): OutboxMessage
    {
        return OutboxMessage::query()->create([
            'tenant_id' => $this->tenantContext->id(),
            'event_type' => $event::class,
            'payload' => $this->serializeEvent($event),
            'status' => OutboxMessage::STATUS_PENDING,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeEvent(object $event): array
    {
        if (method_exists($event, 'toArray')) {
            /** @var array<string, mixed> $payload */
            $payload = $event->toArray();

            return $payload;
        }

        $payload = [];

        foreach ((new \ReflectionClass($event))->getProperties() as $property) {
            $property->setAccessible(true);
            $payload[$property->getName()] = $property->getValue($event);
        }

        return $payload;
    }
}
