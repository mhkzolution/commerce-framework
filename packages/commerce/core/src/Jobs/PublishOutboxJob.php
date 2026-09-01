<?php

declare(strict_types=1);

namespace Commerce\Core\Jobs;

use Commerce\Core\Outbox\OutboxPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class PublishOutboxJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $limit = 100) {}

    public function handle(OutboxPublisher $publisher): void
    {
        $publisher->publishPending($this->limit);
    }
}
