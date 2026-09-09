<?php

declare(strict_types=1);

namespace Commerce\Product\Console;

use Commerce\Product\Services\RecoverProductAttributeValues;
use Illuminate\Console\Command;

final class RestoreProductAttributeValuesCommand extends Command
{
    protected $signature = 'product:restore-attribute-values
        {suffix : Backup suffix, for example 20260909}';

    protected $description = 'Restore product attribute values from recovery backups';

    public function handle(RecoverProductAttributeValues $recovery): int
    {
        $suffix = (string) $this->argument('suffix');
        $recovery->restore($suffix);

        $this->warn('Recovery backup restored. Staff attribute edits made after recovery were replaced.');
        $this->info("Restored attribute values from backup suffix {$suffix}.");

        return self::SUCCESS;
    }
}
