<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_scans', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 32);
            $table->string('sku', 128);
            $table->uuid('variant_uuid')->nullable();
            $table->string('action', 64);
            $table->integer('quantity')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['mode', 'created_at']);
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_scans');
    }
};
