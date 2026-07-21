<?php

declare(strict_types=1);

namespace Commerce\Webhooks\Services;

use Commerce\Webhooks\Models\Webhook;
use Commerce\Webhooks\Models\WebhookDelivery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class WebhookQueryService
{
    public function paginate(?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return Webhook::query()
            ->when($search, function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function recentDeliveries(Webhook $webhook, int $limit = 25): LengthAwarePaginator
    {
        return $webhook->deliveries()
            ->orderByDesc('created_at')
            ->paginate($limit)
            ->withQueryString();
    }

    public function findDelivery(string $uuid): ?WebhookDelivery
    {
        return WebhookDelivery::query()->where('uuid', $uuid)->first();
    }
}
