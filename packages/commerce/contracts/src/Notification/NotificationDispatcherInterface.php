<?php

declare(strict_types=1);

namespace Commerce\Contracts\Notification;

interface NotificationDispatcherInterface
{
    /**
     * @param  array<string, mixed>  $variables
     */
    public function send(string $templateCode, object $recipient, array $variables = []): void;
}
