<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_commissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->uuid('order_uuid');
            $table->uuid('order_line_item_uuid');
            $table->uuid('seller_uuid');
            $table->unsignedInteger('line_total');
            $table->unsignedInteger('commission_rate');
            $table->unsignedInteger('commission_amount');
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->index(['order_uuid']);
            $table->index(['seller_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_commissions');
    }
};
