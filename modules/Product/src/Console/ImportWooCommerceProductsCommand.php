<?php

declare(strict_types=1);

namespace Commerce\Product\Console;

use Commerce\Product\Import\WooCommerceProductImporter;
use Illuminate\Console\Command;

final class ImportWooCommerceProductsCommand extends Command
{
    protected $signature = 'product:import-woocommerce
                            {file : Path to the WooCommerce product CSV export}
                            {--dry-run : Preview import without writing data}
                            {--force : Import even when SKU or WordPress ID already exists}
                            {--limit= : Maximum number of rows to process}
                            {--link-images : Only link images from disk for products already imported}';

    protected $description = 'Import products from a WooCommerce CSV export';

    public function handle(WooCommerceProductImporter $importer): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $limit = $this->option('limit');
        $stats = $importer->import(
            path: $file,
            output: $this->output,
            dryRun: (bool) $this->option('dry-run'),
            skipExisting: ! (bool) $this->option('force'),
            limit: $limit !== null ? (int) $limit : null,
            linkImagesOnly: (bool) $this->option('link-images'),
        );

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Imported', (string) $stats['imported']],
                ['Skipped', (string) $stats['skipped']],
                ['Images linked', (string) $stats['linked_images']],
                ['Errors', (string) $stats['errors']],
            ],
        );

        if ($stats['errors'] > 0) {
            return self::FAILURE;
        }

        if (! $this->option('link-images')) {
            $this->newLine();
            $this->line('Image folder: <info>public/wp-content/uploads/</info>');
            $this->line('Mirror WordPress paths after /uploads/, e.g. <comment>public/wp-content/uploads/2021/03/image.jpg</comment>');
            $this->line('After copying images, run: <comment>php artisan product:import-woocommerce '.$file.' --link-images</comment>');
        }

        return self::SUCCESS;
    }
}
