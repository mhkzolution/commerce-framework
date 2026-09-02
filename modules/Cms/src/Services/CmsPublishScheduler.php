<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\Models\Page;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Support\ScheduledPublishing;
use Illuminate\Database\Eloquent\Model;

final class CmsPublishScheduler
{
    /**
     * @return array{published: int, archived: int}
     */
    public function run(): array
    {
        if (! ScheduledPublishing::enabled()) {
            return ['published' => 0, 'archived' => 0];
        }

        $published = $this->publishDue();
        $archived = $this->archiveExpired();

        return ['published' => $published, 'archived' => $archived];
    }

    private function publishDue(): int
    {
        $count = 0;

        foreach ([Post::class, Page::class] as $model) {
            $count += $this->advanceDue(
                $model,
                status: 'scheduled',
                nextStatus: 'published',
                timestampColumn: 'published_at',
            );
        }

        return $count;
    }

    private function archiveExpired(): int
    {
        $count = 0;

        foreach ([Post::class, Page::class] as $model) {
            $count += $this->advanceDue(
                $model,
                status: 'published',
                nextStatus: 'archived',
                timestampColumn: 'unpublish_at',
            );
        }

        return $count;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function advanceDue(string $model, string $status, string $nextStatus, string $timestampColumn): int
    {
        $count = 0;

        $model::query()
            ->where('status', $status)
            ->whereNotNull($timestampColumn)
            ->where($timestampColumn, '<=', now())
            ->chunkById(100, function ($rows) use (&$count, $nextStatus): void {
                foreach ($rows as $row) {
                    $row->update(['status' => $nextStatus]);
                    $count++;
                }
            });

        return $count;
    }
}
