<?php

declare(strict_types=1);

namespace Commerce\Core\Outbox;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Core\Models\OutboxMessage;
use Illuminate\Support\Facades\Log;

final class OutboxPublisher
{
    public function __construct(private readonly EventBusInterface $eventBus) {}

    public function publishPending(int $limit = 100): int
    {
        $messages = OutboxMessage::query()
            ->where('status', OutboxMessage::STATUS_PENDING)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $published = 0;

        foreach ($messages as $message) {
            try {
                $event = $this->rehydrate($message);

                if ($event !== null) {
                    $this->eventBus->dispatch($event);
                }

                $message->forceFill([
                    'status' => OutboxMessage::STATUS_PUBLISHED,
                    'published_at' => now(),
                    'last_error' => null,
                ])->save();

                $published++;
            } catch (\Throwable $exception) {
                $message->forceFill([
                    'attempts' => $message->attempts + 1,
                    'last_error' => $exception->getMessage(),
                    'status' => $message->attempts >= 5
                        ? OutboxMessage::STATUS_FAILED
                        : OutboxMessage::STATUS_PENDING,
                ])->save();

                Log::warning('Outbox publish failed', [
                    'uuid' => $message->uuid,
                    'event_type' => $message->event_type,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $published;
    }

    private function rehydrate(OutboxMessage $message): ?object
    {
        $class = $message->event_type;

        if (! class_exists($class)) {
            return null;
        }

        $reflection = new \ReflectionClass($class);

        if (! $reflection->isInstantiable()) {
            return null;
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $arguments[] = $message->payload[$name] ?? ($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null);
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
