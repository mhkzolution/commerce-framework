<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_commissions', function (Blueprint $table): void {
            $table->uuid('payout_uuid')->nullable()->after('status');
            $table->index('payout_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_commissions', function (Blueprint $table): void {
            $table->dropIndex(['payout_uuid']);
            $table->dropColumn('payout_uuid');
        });
    }
};
