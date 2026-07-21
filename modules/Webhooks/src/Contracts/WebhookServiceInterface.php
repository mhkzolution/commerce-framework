<?php

declare(strict_types=1);

namespace Commerce\Webhooks\Contracts;

use Commerce\Webhooks\DTO\CreateWebhookData;
use Commerce\Webhooks\DTO\UpdateWebhookData;
use Commerce\Webhooks\Models\Webhook;

interface WebhookServiceInterface
{
    public function create(CreateWebhookData $data): Webhook;

    public function update(string $uuid, UpdateWebhookData $data): Webhook;

    public function delete(string $uuid): void;
}
