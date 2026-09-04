<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->uuid('created_by_user_uuid')->nullable()->after('channel');
            $table->uuid('updated_by_user_uuid')->nullable()->after('created_by_user_uuid');
            $table->uuid('idempotency_key')->nullable()->unique()->after('updated_by_user_uuid');

            $table->index('created_by_user_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
            $table->dropIndex(['created_by_user_uuid']);
            $table->dropColumn(['created_by_user_uuid', 'updated_by_user_uuid', 'idempotency_key']);
        });
    }
};
