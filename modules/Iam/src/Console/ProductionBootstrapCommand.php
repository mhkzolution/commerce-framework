<?php

declare(strict_types=1);

namespace Commerce\Iam\Console;

use Illuminate\Console\Command;

final class ProductionBootstrapCommand extends Command
{
    protected $signature = 'commerce:production-bootstrap {--assign-super-admin : Assign all permissions to super admin}';

    protected $description = 'Sync permissions and print production scheduler checklist';

    public function handle(): int
    {
        $this->call('iam:sync-permissions', $this->option('assign-super-admin') ? ['--assign-super-admin' => true] : []);

        $this->newLine();
        $this->info('Production checklist:');
        $this->line('1. Add cron: * * * * * php '.base_path('artisan').' schedule:run');
        $this->line('2. Scheduled tasks: commerce:outbox:publish (every minute), catalog:sync-automated-collections (hourly)');
        $this->line('3. Run queue worker: php '.base_path('artisan').' queue:work --queue='.config('inventory.purchase_order.queue_name', 'notifications').',default');
        $this->line('4. Run migrations: php artisan migrate --force');

        return self::SUCCESS;
    }
}
