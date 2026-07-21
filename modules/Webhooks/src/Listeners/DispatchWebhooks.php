<?php

declare(strict_types=1);

namespace Commerce\Webhooks\Listeners;

use Commerce\Contracts\Event\DomainEventInterface;
use Commerce\Webhooks\Services\WebhookDispatcher;

final class DispatchWebhooks
{
    public function __construct(
        private readonly WebhookDispatcher $dispatcher,
    ) {}

    public function handle(DomainEventInterface $event): void
    {
        $this->dispatcher->dispatchFromDomainEvent($event);
    }
}
