<?php

declare(strict_types=1);

namespace Commerce\Product\Console;

use Commerce\Product\Services\RecoverProductAttributeValues;
use Illuminate\Console\Command;

final class RecoverProductAttributeValuesCommand extends Command
{
    protected $signature = 'product:recover-attribute-values
        {--audit : Report size/age tokens only}
        {--dry-run : Print the recovery plan without writing}
        {--force : Use a new backup suffix if dated tables exist}';

    protected $description = 'Recover imported product attribute text into catalog values';

    public function handle(RecoverProductAttributeValues $recovery): int
    {
        if ((bool) $this->option('audit')) {
            $this->renderAudit($recovery->audit());

            return self::SUCCESS;
        }

        $result = $recovery->apply(
            dryRun: (bool) $this->option('dry-run'),
            force: (bool) $this->option('force'),
        );

        $this->table(
            ['Metric', 'Value'],
            [
                ['Backup suffix', (string) $result['suffix']],
                ['Dry run', $result['dry_run'] ? 'yes' : 'no'],
                ['Already applied', $result['already_applied'] ? 'yes' : 'no'],
                ['Touched products', (string) $result['touched_products']],
                ['Text rows', (string) ($result['text_rows'] ?? 0)],
            ],
        );
        $this->renderAudit($result['audit']);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $audit
     */
    private function renderAudit(array $audit): void
    {
        $rows = [];
        foreach ($audit['attributes'] as $attribute) {
            $rows[] = [
                $attribute['name'],
                $attribute['code'],
                (string) $attribute['rows'],
                implode(', ', $attribute['raw_tokens']),
                implode(', ', $attribute['canonical_tokens']),
                (string) $attribute['collapses'],
            ];
        }

        $this->table(
            ['Attribute', 'Code', 'Rows', 'Raw tokens', 'Canonical tokens', 'Collapses'],
            $rows,
        );
    }
}
