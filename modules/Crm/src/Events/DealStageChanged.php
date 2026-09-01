<?php

declare(strict_types=1);

namespace Commerce\Crm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DealStageChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $dealUuid,
        public readonly string $fromStage,
        public readonly string $toStage,
    ) {}
}
