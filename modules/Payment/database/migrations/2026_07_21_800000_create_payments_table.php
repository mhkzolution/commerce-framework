<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->uuid('order_uuid');
            $table->bigInteger('amount');
            $table->char('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending');
            $table->string('method', 30)->default('manual');
            $table->string('gateway_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['order_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
