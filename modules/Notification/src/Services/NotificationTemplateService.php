<?php

declare(strict_types=1);

namespace Commerce\Notification\Services;

use Commerce\Notification\Models\NotificationTemplate;
use Illuminate\Support\Collection;

final class NotificationTemplateService
{
    public function findByCode(string $code): ?NotificationTemplate
    {
        return NotificationTemplate::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @return Collection<int, NotificationTemplate>
     */
    public function all(): Collection
    {
        return NotificationTemplate::query()->orderBy('code')->get();
    }

    public function upsert(string $code, string $name, string $subject, string $view, string $channel = 'email'): NotificationTemplate
    {
        return NotificationTemplate::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'channel' => $channel,
                'subject' => $subject,
                'view' => $view,
                'is_active' => true,
            ],
        );
    }
}
