<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_print_jobs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('type', 32);
            $table->string('template', 64);
            $table->string('paper_width', 8);
            $table->string('renderer', 32);
            $table->string('slip_number')->nullable();
            $table->json('payload');
            $table->string('status', 32)->default('created');
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
            $table->index('slip_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_print_jobs');
    }
};
