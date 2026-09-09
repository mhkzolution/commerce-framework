<?php

declare(strict_types=1);

namespace Commerce\Product\Console;

use Commerce\Product\Import\ProductLocalImageAttacher;
use Illuminate\Console\Command;

final class AttachLocalProductImagesCommand extends Command
{
    protected $signature = 'product:attach-local-images
                            {--mapping=doc/products-image-mapping-report.md : Mapping markdown or JSON}
                            {--source=doc/uploads : Local WordPress uploads root}
                            {--dry-run : Preview attach without writing data}
                            {--force : Replace product_media for mapped SKUs that already have images}
                            {--limit= : Maximum number of mapped SKUs to process}
                            {--resume-from-sku= : Start at this SKU (inclusive)}';

    protected $description = 'Attach local PPK image files to existing products via product_media';

    public function handle(ProductLocalImageAttacher $attacher): int
    {
        $mapping = (string) $this->option('mapping');
        $source = (string) $this->option('source');

        if (! is_file($mapping)) {
            $this->error('Mapping file not found: '.$mapping);

            return self::FAILURE;
        }

        if (! is_dir($source)) {
            $this->error('Source directory not found: '.$source);

            return self::FAILURE;
        }

        $limit = $this->option('limit');
        $resume = $this->option('resume-from-sku');

        $stats = $attacher->attach(
            mappingPath: $mapping,
            sourceRoot: $source,
            dryRun: (bool) $this->option('dry-run'),
            force: (bool) $this->option('force'),
            limit: $limit !== null && $limit !== '' ? (int) $limit : null,
            resumeFromSku: is_string($resume) && $resume !== '' ? $resume : null,
        );

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Products attached', (string) $stats['products_attached']],
                ['Products skipped', (string) $stats['products_skipped']],
                ['Missing products', (string) $stats['missing_products']],
                ['Media created', (string) $stats['media_created']],
                ['Media reused', (string) $stats['media_reused']],
                ['Product media', (string) $stats['product_media']],
                ['Missing files', (string) $stats['missing_files']],
                ['Warnings', (string) $stats['warnings']],
                ['Errors', (string) $stats['errors']],
            ],
        );

        if ((bool) $this->option('dry-run')) {
            $this->comment('Dry-run: no media or product_media rows were written.');
        }

        return self::SUCCESS;
    }
}
