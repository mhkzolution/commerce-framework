<?php

declare(strict_types=1);

namespace Commerce\Webhooks\Support;

use Commerce\Webhooks\DTO\CreateWebhookData;
use Commerce\Webhooks\DTO\UpdateWebhookData;

final class WebhookFormData
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function toCreateData(array $validated): CreateWebhookData
    {
        return new CreateWebhookData(
            name: (string) $validated['name'],
            url: (string) $validated['url'],
            secret: (string) ($validated['secret'] ?? ''),
            events: array_values($validated['events'] ?? []),
            isActive: (bool) ($validated['is_active'] ?? true),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function toUpdateData(array $validated): UpdateWebhookData
    {
        $secret = isset($validated['secret']) && $validated['secret'] !== ''
            ? (string) $validated['secret']
            : null;

        return new UpdateWebhookData(
            name: (string) $validated['name'],
            url: (string) $validated['url'],
            secret: $secret,
            events: array_values($validated['events'] ?? []),
            isActive: (bool) ($validated['is_active'] ?? false),
        );
    }
}
