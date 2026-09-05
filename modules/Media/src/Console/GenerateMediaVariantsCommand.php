<?php

declare(strict_types=1);

namespace Commerce\Media\Console;

use Commerce\Media\Models\Media;
use Commerce\Media\Support\ImageVariantGenerator;
use Illuminate\Console\Command;

final class GenerateMediaVariantsCommand extends Command
{
    protected $signature = 'media:generate-variants {--force : Recreate existing variants}';

    protected $description = 'Generate WebP image variants for uploaded media';

    public function handle(ImageVariantGenerator $generator): int
    {
        $force = (bool) $this->option('force');
        $processed = 0;

        Media::query()
            ->where('media_type', 'image')
            ->orderBy('id')
            ->chunkById(50, function ($chunk) use ($generator, $force, &$processed): void {
                foreach ($chunk as $media) {
                    $generator->generate($media, $force);
                    $processed++;
                }
            });

        $this->info("Processed {$processed} media records.");

        return self::SUCCESS;
    }
}
