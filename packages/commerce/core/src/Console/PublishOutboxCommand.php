<?php

declare(strict_types=1);

namespace Commerce\Core\Console;

use Commerce\Core\Jobs\PublishOutboxJob;
use Commerce\Core\Outbox\OutboxPublisher;
use Illuminate\Console\Command;

final class PublishOutboxCommand extends Command
{
    protected $signature = 'commerce:outbox:publish {--limit=100 : Maximum messages to publish} {--queue : Dispatch a queue job instead of publishing synchronously}';

    protected $description = 'Publish pending outbox messages to the event bus';

    public function handle(OutboxPublisher $publisher): int
    {
        if ($this->option('queue')) {
            PublishOutboxJob::dispatch((int) $this->option('limit'));
            $this->info('Outbox publish job dispatched.');

            return self::SUCCESS;
        }

        $count = $publisher->publishPending((int) $this->option('limit'));

        $this->info("Published {$count} outbox message(s).");

        return self::SUCCESS;
    }
}
