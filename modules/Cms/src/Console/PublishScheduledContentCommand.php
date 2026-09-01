<?php

declare(strict_types=1);

namespace Commerce\Cms\Console;

use Commerce\Cms\Services\CmsPublishScheduler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class PublishScheduledContentCommand extends Command
{
    protected $signature = 'cms:publish-scheduled';

    protected $description = 'Publish due scheduled CMS content and archive expired published content';

    public function handle(CmsPublishScheduler $scheduler): int
    {
        $result = $scheduler->run();

        if (($result['published'] + $result['archived']) > 0) {
            Log::info('cms.publish-scheduled', $result);
        }

        $this->info("Published {$result['published']}, archived {$result['archived']}.");

        return self::SUCCESS;
    }
}
