<?php

declare(strict_types=1);

namespace Commerce\Webhooks\DTO;

final readonly class CreateWebhookData
{
    /**
     * @param  list<string>  $events
     */
    public function __construct(
        public string $name,
        public string $url,
        public string $secret,
        public array $events,
        public bool $isActive = true,
    ) {}
}
