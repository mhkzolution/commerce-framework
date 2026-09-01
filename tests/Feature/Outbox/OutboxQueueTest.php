<?php

declare(strict_types=1);

namespace Tests\Feature\Outbox;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Core\Jobs\PublishOutboxJob;
use Commerce\Core\Models\OutboxMessage;
use Commerce\Orders\Events\OrderCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class OutboxQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_reliable_dispatches_queue_job_when_configured(): void
    {
        Bus::fake();
        config([
            'commerce.outbox.auto_publish' => true,
            'commerce.outbox.use_queue' => true,
        ]);

        app(EventBusInterface::class)->dispatchReliable(new OrderCreated(
            orderUuid: (string) str()->uuid(),
            orderNumber: 'ORD-QUEUE-1',
        ));

        Bus::assertDispatched(PublishOutboxJob::class);
        $this->assertDatabaseHas('outbox_messages', [
            'event_type' => OrderCreated::class,
            'status' => OutboxMessage::STATUS_PENDING,
        ]);
    }
}
