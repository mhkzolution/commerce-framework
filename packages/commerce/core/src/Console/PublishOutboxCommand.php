<?php

declare(strict_types=1);

namespace Commerce\Core\Console;

use Commerce\Core\Outbox\OutboxPublisher;
use Illuminate\Console\Command;

final class PublishOutboxCommand extends Command
{
    protected $signature = 'commerce:outbox:publish {--limit=100 : Maximum messages to publish}';

    protected $description = 'Publish pending outbox messages to the event bus';

    public function handle(OutboxPublisher $publisher): int
    {
        $count = $publisher->publishPending((int) $this->option('limit'));

        $this->info("Published {$count} outbox message(s).");

        return self::SUCCESS;
    }
}
