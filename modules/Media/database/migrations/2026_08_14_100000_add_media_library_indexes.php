<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->index('filename');
            $table->index('original_filename');
            $table->index('alt_text');
            $table->index('mime_type');
            $table->index('created_at');
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::table('media_folders', function (Blueprint $table): void {
            $table->index('name');
            $table->index(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->dropIndex(['filename']);
            $table->dropIndex(['original_filename']);
            $table->dropIndex(['alt_text']);
            $table->dropIndex(['mime_type']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['tenant_id', 'created_at']);
        });

        Schema::table('media_folders', function (Blueprint $table): void {
            $table->dropIndex(['name']);
            $table->dropIndex(['tenant_id', 'name']);
        });
    }
};
