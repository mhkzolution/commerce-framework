<?php

declare(strict_types=1);

namespace Commerce\Core\Events;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Core\Outbox\OutboxRecorder;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;

final class EventBus implements EventBusInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly OutboxRecorder $outbox,
    ) {}

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }

    public function dispatchReliable(object $event): void
    {
        $this->outbox->record($event);
    }

    public function dispatchAsync(object $event): void
    {
        if ($event instanceof ShouldQueue) {
            $this->dispatcher->dispatch($event);

            return;
        }

        $this->dispatcher->dispatch($event);
    }

    public function listen(string $event, callable|string $listener, bool $async = false): void
    {
        $this->dispatcher->listen($event, $listener);
    }
}
