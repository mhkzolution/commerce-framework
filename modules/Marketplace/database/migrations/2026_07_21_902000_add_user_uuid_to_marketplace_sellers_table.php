<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_sellers', function (Blueprint $table): void {
            $table->uuid('user_uuid')->nullable()->after('tenant_id');
            $table->index('user_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_sellers', function (Blueprint $table): void {
            $table->dropIndex(['user_uuid']);
            $table->dropColumn('user_uuid');
        });
    }
};
