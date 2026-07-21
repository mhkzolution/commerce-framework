<?php

declare(strict_types=1);

namespace Commerce\Webhooks\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Webhooks\Contracts\WebhookServiceInterface;
use Commerce\Webhooks\DTO\CreateWebhookData;
use Commerce\Webhooks\DTO\UpdateWebhookData;
use Commerce\Webhooks\Models\Webhook;
use Illuminate\Support\Str;

final class WebhookService extends BaseService implements WebhookServiceInterface
{
    public function create(CreateWebhookData $data): Webhook
    {
        return Webhook::query()->create([
            'name' => $data->name,
            'url' => $data->url,
            'secret' => $data->secret !== '' ? $data->secret : (string) Str::uuid(),
            'events' => $data->events,
            'is_active' => $data->isActive,
        ]);
    }

    public function update(string $uuid, UpdateWebhookData $data): Webhook
    {
        $webhook = $this->findOrFail($uuid);

        $attributes = [
            'name' => $data->name,
            'url' => $data->url,
            'events' => $data->events,
            'is_active' => $data->isActive,
        ];

        if ($data->secret !== null) {
            $attributes['secret'] = $data->secret;
        }

        $webhook->update($attributes);

        return $webhook->fresh();
    }

    public function delete(string $uuid): void
    {
        $this->findOrFail($uuid)->delete();
    }

    private function findOrFail(string $uuid): Webhook
    {
        $webhook = Webhook::query()->where('uuid', $uuid)->first();

        if ($webhook === null) {
            throw new EntityNotFoundException("Webhook [{$uuid}] not found.");
        }

        return $webhook;
    }
}
