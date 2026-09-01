<?php

declare(strict_types=1);

namespace Commerce\Core\Events;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Core\Jobs\PublishOutboxJob;
use Commerce\Core\Outbox\OutboxPublisher;
use Commerce\Core\Outbox\OutboxRecorder;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

final class EventBus implements EventBusInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly OutboxRecorder $outbox,
        private readonly OutboxPublisher $publisher,
    ) {}

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }

    public function dispatchReliable(object $event): void
    {
        $this->outbox->record($event);

        if (! config('commerce.outbox.auto_publish', true)) {
            return;
        }

        DB::afterCommit(function (): void {
            if (config('commerce.outbox.use_queue', false)) {
                PublishOutboxJob::dispatch();

                return;
            }

            $this->publisher->publishPending();
        });
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
