<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('type', 32);
            $table->string('number', 32);
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('status', 20)->default('issued');
            $table->timestamp('issued_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->json('payload');
            $table->bigInteger('grand_total')->default(0);
            $table->char('currency', 3);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'type', 'status', 'issued_at']);
            $table->index(['source_type', 'source_id']);
            $table->index('number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
