<?php

declare(strict_types=1);

namespace Commerce\Cms\Support;

final class ScheduledPublishing
{
    public const FEATURE = 'scheduled-publishing';

    public static function enabled(): bool
    {
        return feature(self::FEATURE);
    }

    /**
     * @return array<string, string>
     */
    public static function editorStatuses(?string $currentStatus = null): array
    {
        $statuses = config('cms.statuses', []);

        if (! self::enabled() && $currentStatus !== 'scheduled') {
            unset($statuses['scheduled']);
        }

        return $statuses;
    }

    /**
     * @return list<string>
     */
    public static function allowedStatuses(?string $currentStatus = null): array
    {
        return array_keys(self::editorStatuses($currentStatus));
    }
}
