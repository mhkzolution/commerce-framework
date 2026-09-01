<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barcode_print_jobs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('barcode_template_id')->nullable()->constrained('barcode_templates')->nullOnDelete();
            $table->unsignedBigInteger('printed_by_user_id')->nullable();
            $table->unsignedInteger('label_count')->default(0);
            $table->string('paper_size', 32)->nullable();
            $table->string('template_name')->nullable();
            $table->string('status', 32)->default('completed');
            $table->json('settings')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'printed_at']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_print_jobs');
    }
};
