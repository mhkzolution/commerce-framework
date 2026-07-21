<?php

declare(strict_types=1);

namespace Commerce\Media\Listeners;

use Commerce\Media\Events\MediaUploaded;
use Commerce\Media\Models\Media;
use Commerce\Media\Support\ImageVariantGenerator;

final class GenerateMediaVariants
{
    public function __construct(
        private readonly ImageVariantGenerator $generator,
    ) {}

    public function handle(MediaUploaded $event): void
    {
        $media = Media::query()->where('uuid', $event->mediaUuid)->first();

        if ($media === null) {
            return;
        }

        $this->generator->generate($media);
    }
}
