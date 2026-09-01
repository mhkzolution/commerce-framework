<?php

declare(strict_types=1);

namespace Tests\Feature\Outbox;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Core\Models\OutboxMessage;
use Commerce\Core\Outbox\OutboxPublisher;
use Commerce\Orders\Events\OrderCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OutboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_reliable_records_outbox_message(): void
    {
        config(['commerce.outbox.auto_publish' => false]);

        app(EventBusInterface::class)->dispatchReliable(new OrderCreated(
            orderUuid: (string) str()->uuid(),
            orderNumber: 'ORD-1001',
        ));

        $this->assertDatabaseHas('outbox_messages', [
            'event_type' => OrderCreated::class,
            'status' => OutboxMessage::STATUS_PENDING,
        ]);
    }

    public function test_outbox_publisher_marks_messages_as_published(): void
    {
        config(['commerce.outbox.auto_publish' => false]);

        app(EventBusInterface::class)->dispatchReliable(new OrderCreated(
            orderUuid: (string) str()->uuid(),
            orderNumber: 'ORD-1002',
        ));

        $published = app(OutboxPublisher::class)->publishPending();

        $this->assertSame(1, $published);
        $this->assertDatabaseHas('outbox_messages', [
            'event_type' => OrderCreated::class,
            'status' => OutboxMessage::STATUS_PUBLISHED,
        ]);
    }

    public function test_auto_publish_publishes_after_commit(): void
    {
        config(['commerce.outbox.auto_publish' => true]);

        app(EventBusInterface::class)->dispatchReliable(new OrderCreated(
            orderUuid: (string) str()->uuid(),
            orderNumber: 'ORD-1003',
        ));

        $this->assertDatabaseHas('outbox_messages', [
            'event_type' => OrderCreated::class,
            'status' => OutboxMessage::STATUS_PUBLISHED,
        ]);
    }
}
