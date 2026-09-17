<?php

declare(strict_types=1);

/**
 * Worker for concurrent document number allocation tests.
 *
 * Usage: php allocate_document_number_worker.php <sqlite-path> <count> <period-YYYYMM> <base-path>
 */

use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Services\DocumentSequenceService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if ($argc < 5) {
    fwrite(STDERR, "Usage: php allocate_document_number_worker.php <sqlite-path> <count> <period> <base-path>\n");
    exit(1);
}

[, $database, $count, $period, $basePath] = $argv;

putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE='.$database);
putenv('APP_ENV=testing');
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $database;
$_ENV['APP_ENV'] = 'testing';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = $database;
$_SERVER['APP_ENV'] = 'testing';

require $basePath.'/vendor/autoload.php';

$app = require $basePath.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

config([
    'database.default' => 'sqlite',
    'database.connections.sqlite.database' => $database,
    'database.connections.sqlite.busy_timeout' => 15000,
    'database.connections.sqlite.journal_mode' => 'wal',
    'database.connections.sqlite.transaction_mode' => 'IMMEDIATE',
    'commerce.modules.documents' => true,
]);

DB::purge('sqlite');
DB::reconnect('sqlite');

if (! Schema::hasTable('document_sequences')) {
    fwrite(STDERR, "document_sequences table is missing\n");
    exit(1);
}

$service = $app->make(DocumentSequenceService::class);
$at = Carbon::createFromFormat('Ym', $period)->startOfMonth();
$numbers = [];

foreach (range(1, (int) $count) as $ignored) {
    $numbers[] = $service->allocate(DocumentType::TaxInvoice, $at);
}

echo json_encode($numbers, JSON_THROW_ON_ERROR);
